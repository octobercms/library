<?php namespace October\Rain\Database\Traits;

use DbDongle;
use October\Rain\Database\Collection;
use October\Rain\Database\TreeCollection;
use October\Rain\Database\Scopes\NestedTreeScope;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Exception;

/**
 * NestedTree is a nested set model trait
 *
 * Model table must have parent_id, nest_left, nest_right and nest_depth table columns.
 * In the model class definition:
 *
 *   use \October\Rain\Database\Traits\NestedTree;
 *
 *   $table->integer('parent_id')->nullable();
 *   $table->integer('nest_left')->nullable();
 *   $table->integer('nest_right')->nullable();
 *   $table->integer('nest_depth')->nullable();
 *
 * You can change the column names used by declaring:
 *
 *   const PARENT_ID = 'my_parent_column';
 *   const NEST_LEFT = 'my_left_column';
 *   const NEST_RIGHT = 'my_right_column';
 *   const NEST_DEPTH = 'my_depth_column';
 *
 * General access methods:
 *
 *   $model->getRoot(); // Returns the highest parent of a node.
 *   $model->getRootList(); // Returns an indented array of key and value columns from root.
 *   $model->getParent(); // The direct parent node.
 *   $model->getParents(); // Returns all parents up the tree.
 *   $model->getParentsAndSelf(); // Returns all parents up the tree and self.
 *   $model->getChildren(); // Set of all direct child nodes.
 *   $model->getSiblings(); // Return all siblings (parent's children).
 *   $model->getSiblingsAndSelf(); // Return all siblings and self.
 *   $model->getLeaves(); // Returns all final nodes without children.
 *   $model->getDepth(); // Returns the depth of a current node.
 *   $model->getChildCount(); // Returns number of all children.
 *
 * Query builder methods:
 *
 *   $query->withoutNode(); // Filters a specific node from the results.
 *   $query->withoutSelf(); // Filters current node from the results.
 *   $query->withoutRoot(); // Filters root from the results.
 *   $query->children(); // Filters as direct children down the tree.
 *   $query->allChildren(); // Filters as all children down the tree.
 *   $query->parent(); // Filters as direct parent up the tree.
 *   $query->parents(); // Filters as all parents up the tree.
 *   $query->siblings(); // Filters as all siblings (parent's children).
 *   $query->leaves(); // Filters as all final nodes without children.
 *   $query->getNested(); // Returns an eager loaded collection of results.
 *   $query->listsNested(); // Returns an indented array of key and value columns.
 *
 * Flat result access methods:
 *
 *   $model->getAll(); // Returns everything in correct order.
 *   $model->getAllRoot(); // Returns all root nodes.
 *   $model->getAllChildren(); // Returns all children down the tree.
 *   $model->getAllChildrenAndSelf(); // Returns all children and self.
 *
 * Eager loaded access methods:
 *
 *   $model->getEagerRoot(); // Returns a list of all root nodes, with ->children eager loaded.
 *   $model->getEagerChildren(); // Returns direct child nodes, with ->children eager loaded.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait NestedTree
{
    /**
     * @var int moveToNewParentId indicates if the model should be aligned to new parent.
     */
    protected $moveToNewParentId = false;

    /**
     * bootNestedTree constructor
     */
    public static function bootNestedTree()
    {
        static::addGlobalScope(new NestedTreeScope);
    }

    /**
     * initializeNestedTree constructor
     */
    public function initializeNestedTree()
    {
        // Define relationships
        $this->hasMany['children'] = [
            static::class,
            'key' => $this->getParentColumnName(),
            'replicate' => false
        ];

        $this->belongsTo['parent'] = [
            static::class,
            'key' => $this->getParentColumnName(),
            'replicate' => false
        ];

        // Bind events
        $this->bindEvent('model.beforeCreate', function () {
            $this->setDefaultLeftAndRight();
        });

        $this->bindEvent('model.beforeSave', function () {
            $this->storeNewParent();
        });

        $this->bindEvent('model.afterSave', function () {
            $this->moveToNewParent();
        });

        $this->bindEvent('model.beforeDelete', function () {
            $this->deleteDescendants();
        });

        if (static::hasGlobalScope(SoftDeletingScope::class)) {
            $this->bindEvent('model.beforeRestore', function () {
                $this->shiftSiblingsForRestore();
            });

            $this->bindEvent('model.afterRestore', function () {
                $this->restoreDescendants();
            });
        }
    }

    /**
     * storeNewParent handles if the parent column is modified so it can be realigned.
     */
    public function storeNewParent()
    {
        // Has the parent column been set from the outside
        $parentId = $this->getParentId();
        $parentOld = $this->getOriginal($this->getParentColumnName());
        $isDirty = (int) $parentOld !== (int) $parentId;

        // The parent ID column is nullable, including zero values,
        // skipping this logic if the parent ID is already null.
        if (!$parentId && $parentId !== null) {
            $this->setAttribute($this->getParentColumnName(), null);
            $parentId = null;
        }

        // Parent is not set or unchanged
        if (!$isDirty) {
            $this->moveToNewParentId = false;
        }
        // Created as a root node
        elseif (!$this->exists && !$parentId) {
            $this->moveToNewParentId = false;
        }
        // Parent has been set
        else {
            $this->moveToNewParentId = $parentId;
        }
    }

    /**
     * moveToNewParent will realign the nesting if the parent identifier is dirty.
     */
    public function moveToNewParent()
    {
        $parentId = $this->moveToNewParentId;
        if ($parentId === false) {
            return;
        }

        if ($parentId === null) {
            $this->makeRoot();
            return;
        }

        $parentModel = $this->resolveMoveTarget($parentId);
        if ($parentModel) {
            $this->makeChildOf($parentModel);
            return;
        }

        // Nullify parent since nothing valid was found
        $this->newNestedTreeQuery()
            ->where($this->getKeyName(), $this->getKey())
            ->update([$this->getParentColumnName() => null]);
    }

    /**
     * deleteDescendants deletes a branch off the tree, shifting all the elements on the right
     * back to the left so the counts work.
     */
    public function deleteDescendants()
    {
        if ($this->getRight() === null || $this->getLeft() === null) {
            return;
        }

        $this->getConnection()->transaction(function () {
            $this->reload();

            $leftCol = $this->getLeftColumnName();
            $rightCol = $this->getRightColumnName();
            $left = $this->getLeft();
            $right = $this->getRight();

            // Delete children
            $this->newNestedTreeQuery()
                ->where($leftCol, '>', $left)
                ->where($rightCol, '<', $right)
                ->delete()
            ;

            // Update left and right indexes for the remaining nodes
            $diff = $right - $left + 1;

            $this->newNestedTreeQuery()
                ->where($leftCol, '>', $right)
                ->decrement($leftCol, $diff)
            ;

            $this->newNestedTreeQuery()
                ->where($rightCol, '>', $right)
                ->decrement($rightCol, $diff)
            ;
        });
    }

    /**
     * shiftSiblingsForRestore allocates a slot for the the current node between its siblings.
     */
    public function shiftSiblingsForRestore()
    {
        if ($this->getRight() === null || $this->getLeft() === null) {
            return;
        }

        $this->getConnection()->transaction(function () {
            $leftCol = $this->getLeftColumnName();
            $rightCol = $this->getRightColumnName();
            $left = $this->getLeft();
            $right = $this->getRight();

            // Update left and right indexes for the remaining nodes
            $diff = $right - $left + 1;

            $this->newNestedTreeQuery()
                ->where($leftCol, '>=', $left)
                ->increment($leftCol, $diff)
            ;

            $this->newNestedTreeQuery()
                ->where($rightCol, '>=', $left)
                ->increment($rightCol, $diff)
            ;
        });
    }

    /**
     * restoreDescendants of the current node.
     */
    public function restoreDescendants()
    {
        if ($this->getRight() === null || $this->getLeft() === null) {
            return;
        }

        $this->getConnection()->transaction(function () {
            $this->newNestedTreeQuery()
                ->withTrashed()
                ->where($this->getLeftColumnName(), '>', $this->getLeft())
                ->where($this->getRightColumnName(), '<', $this->getRight())
                ->update([
                    $this->getDeletedAtColumn() => null,
                    $this->getUpdatedAtColumn() => $this->{$this->getUpdatedAtColumn()}
                ])
            ;
        });
    }

    //
    // Alignment
    //

    /**
     * makeRoot makes this model a root node.
     * @return \October\Rain\Database\Model
     */
    public function makeRoot()
    {
        return $this->moveAfter($this->getRoot());
    }

    /**
     * makeChildOf makes model node a child of specified node.
     * @return \October\Rain\Database\Model
     */
    public function makeChildOf($node)
    {
        return $this->moveTo($node, 'child');
    }

    /**
     * moveLeft finds the left sibling and move to left of it.
     * @return \October\Rain\Database\Model
     */
    public function moveLeft()
    {
        return $this->moveBefore($this->getLeftSibling());
    }

    /**
     * moveRight finds the right sibling and move to the right of it.
     * @return \October\Rain\Database\Model
     */
    public function moveRight()
    {
        return $this->moveAfter($this->getRightSibling());
    }

    /**
     * moveBefore moves to the model to before (left) specified node.
     * @return \October\Rain\Database\Model
     */
    public function moveBefore($node)
    {
        return $this->moveTo($node, 'left');
    }

    /**
     * moveAfter moves to the model to after (right) a specified node.
     * @return \October\Rain\Database\Model
     */
    public function moveAfter($node)
    {
        return $this->moveTo($node, 'right');
    }

    //
    // Checkers
    //

    /**
     * isRoot returns true if this is a root node.
     */
    public function isRoot(): bool
    {
        return $this->getParentId() === null;
    }

    /**
     * isChild returns true if this is a child node.
     */
    public function isChild(): bool
    {
        return !$this->isRoot();
    }

    /**
     * isLeaf returns true if this is a leaf node (end of a branch).
     */
    public function isLeaf(): bool
    {
        return $this->exists && ($this->getRight() - $this->getLeft() === 1);
    }

    /**
     * isInsideSubtree checks if the supplied node is inside the subtree of this model.
     * @param \Model
     */
    public function isInsideSubtree($node): bool
    {
        return (
            $this->getLeft() >= $node->getLeft() &&
            $this->getLeft() <= $node->getRight() &&
            $this->getRight() >= $node->getLeft() &&
            $this->getRight() <= $node->getRight()
        );
    }

    /**
     * isDescendantOf returns true if node is a descendant.
     * @param NestedSet
     */
    public function isDescendantOf($other): bool
    {
        return ($this->getLeft() > $other->getLeft() && $this->getLeft() < $other->getRight());
    }

    //
    // Scopes
    //

    /**
     * scopeWithoutNode extracts a certain node object from the current query expression.
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeWithoutNode($query, $node)
    {
        return $query->where($node->getKeyName(), '!=', $node->getKey());
    }

    /**
     * scopeWithoutSelf extracts current node (self) from current query expression.
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeWithoutSelf($query)
    {
        return $this->scopeWithoutNode($query, $this);
    }

    /**
     * scopeWithoutRoot extracts first root (from the current node context) from current
     * query expression.
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeWithoutRoot($query)
    {
        return $this->scopeWithoutNode($query, $this->getRoot());
    }

    /**
     * scopeAllChildren sets of all children & nested children.
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeAllChildren($query, $includeSelf = false)
    {
        $query
            ->where($this->getLeftColumnName(), '>=', $this->getLeft())
            ->where($this->getLeftColumnName(), '<', $this->getRight())
        ;

        return $includeSelf ? $query : $query->withoutSelf();
    }

    /**
     * scopeParents returns a prepared query with all parents up the tree.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParents($query, $includeSelf = false)
    {
        $query
            ->where($this->getLeftColumnName(), '<=', $this->getLeft())
            ->where($this->getRightColumnName(), '>=', $this->getRight())
        ;

        return $includeSelf ? $query : $query->withoutSelf();
    }

    /**
     * scopeSiblings filters targeting all children of the parent, except self.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSiblings($query, $includeSelf = false)
    {
        $query->where($this->getParentColumnName(), $this->getParentId());

        return $includeSelf ? $query : $query->withoutSelf();
    }

    /**
     * scopeLeaves returns all final nodes without children.
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeLeaves($query)
    {
        $grammar = $this->getConnection()->getQueryGrammar();

        $rightCol = $grammar->wrap($this->getQualifiedRightColumnName());
        $leftCol = $grammar->wrap($this->getQualifiedLeftColumnName());

        return $query
            ->allChildren()
            ->whereRaw($rightCol . ' - ' . $leftCol . ' = 1')
        ;
    }

    /**
     * scopeGetAllRoot returns a list of all root nodes, without eager loading.
     * @return \October\Rain\Database\Collection
     */
    public function scopeGetAllRoot($query)
    {
        return $query
            ->where(function ($query) {
                $query->whereNull($this->getParentColumnName());
                $query->orWhere($this->getParentColumnName(), 0);
            })
            ->get()
        ;
    }

    /**
     * scopeGetNested is a non-chaining scope, returns an eager loaded hierarchy tree.
     * Children are eager loaded inside the $model->children relation.
     * @return Collection A collection
     */
    public function scopeGetNested($query)
    {
        return $query->get()->toNested();
    }

    /**
     * scopeListsNested gets an array with values of a given column. Values are indented
     * according to their depth.
     * @param  string $column Array values
     * @param  string $key    Array keys
     * @param  string $indent Character to indent depth
     * @return array
     */
    public function scopeListsNested($query, $column, $key = null, $indent = '&nbsp;&nbsp;&nbsp;')
    {
        $resultKeyName = $this->getKeyName();
        $columns = [$this->getDepthColumnName(), $this->getParentColumnName(), $this->getKeyName(), $column];
        if ($key !== null) {
            $resultKeyName = $key;
            $columns[] = $key;
        }

        $values = $parentIds = [];
        $results = $query->orderBy($this->getLeftColumnName())->get($columns);
        foreach ($results as $result) {
            $parentId = $result->{$this->getParentColumnName()};
            if ($parentId && !isset($parentIds[$parentId])) {
                continue;
            }

            $parentIds[$result->{$this->getKeyName()}] = true;
            $values[$result->{$resultKeyName}] = str_repeat(
                $indent,
                $result->{$this->getDepthColumnName()}
            ) . $result->{$column};
        }

        return $values;
    }

    //
    // Getters
    //

    /**
     * getAll returns all nodes and children.
     * @return \October\Rain\Database\Collection
     */
    public function getAll($columns = ['*'])
    {
        return $this->newNestedTreeQuery()->get($columns);
    }

    /**
     * getRoot returns the root node starting from the current node.
     * @return \October\Rain\Database\Model
     */
    public function getRoot()
    {
        if ($this->exists) {
            return $this->newNestedTreeQuery()->parents(true)
                ->where(function ($query) {
                    $query->whereNull($this->getParentColumnName());
                    $query->orWhere($this->getParentColumnName(), 0);
                })
                ->first()
            ;
        }

        $parentId = $this->getParentId();

        if ($parentId !== null && ($currentParent = $this->newNestedTreeQuery()->find($parentId))) {
            return $currentParent->getRoot();
        }

        return $this;
    }

    /**
     * getEagerRoot returns a list of all root nodes, with children eager loaded.
     * @return \October\Rain\Database\Collection
     */
    public function getEagerRoot()
    {
        return $this->newNestedTreeQuery()->getNested();
    }

    /**
     * getRootList returns an array column/key pair of all root nodes, with children eager loaded.
     * @return array
     */
    public function getRootList($column, $key = null, $indent = '&nbsp;&nbsp;&nbsp;')
    {
        return $this->newNestedTreeQuery()->listsNested($column, $key, $indent);
    }

    /**
     * getParent returns the direct parent node.
     * @return \October\Rain\Database\Collection
     */
    public function getParent()
    {
        return $this->parent()->get();
    }

    /**
     * getParents returns all parents up the tree.
     * @return \October\Rain\Database\Collection
     */
    public function getParents()
    {
        return $this->newNestedTreeQuery()->parents()->get();
    }

    /**
     * getParentsAndSelf returns all parents up the tree and self.
     * @return \October\Rain\Database\Collection
     */
    public function getParentsAndSelf()
    {
        return $this->newNestedTreeQuery()->parents(true)->get();
    }

    /**
     * getChildren returns direct child nodes.
     * @return \October\Rain\Database\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * getEagerChildren returns direct child nodes, with ->children eager loaded.
     * @return \October\Rain\Database\Collection
     */
    public function getEagerChildren()
    {
        return $this->newNestedTreeQuery()->allChildren()->getNested();
    }

    /**
     * getAllChildren returns all children down the tree.
     * @return \October\Rain\Database\Collection
     */
    public function getAllChildren()
    {
        return $this->newNestedTreeQuery()->allChildren()->get();
    }

    /**
     * getAllChildrenAndSelf returns all children and self.
     * @return \October\Rain\Database\Collection
     */
    public function getAllChildrenAndSelf()
    {
        return $this->newNestedTreeQuery()->allChildren(true)->get();
    }

    /**
     * getSiblings returns all siblings (parent's children).
     * @return \October\Rain\Database\Collection
     */
    public function getSiblings()
    {
        return $this->newNestedTreeQuery()->siblings()->get();
    }

    /**
     * getSiblingsAndSelf
     * @return \October\Rain\Database\Collection
     */
    public function getSiblingsAndSelf()
    {
        return $this->newNestedTreeQuery()->siblings(true)->get();
    }

    /**
     * getLeftSibling
     * @return \October\Rain\Database\Model
     */
    public function getLeftSibling()
    {
        return $this->siblings()->where($this->getRightColumnName(), $this->getLeft() - 1)->first();
    }

    /**
     * getRightSibling
     * @return \October\Rain\Database\Model
     */
    public function getRightSibling()
    {
        return $this->siblings()->where($this->getLeftColumnName(), $this->getRight() + 1)->first();
    }

    /**
     * getLeaves returns all final nodes without children.
     * @return \October\Rain\Database\Collection
     */
    public function getLeaves()
    {
        return $this->newNestedTreeQuery()->leaves()->get();
    }

    /**
     * getLevel returns the level of this node in the tree. Root level is 0.
     * @return int
     */
    public function getLevel()
    {
        if ($this->getParentId() === null) {
            return 0;
        }

        return $this->newNestedTreeQuery()->parents()->count();
    }

    /**
     * getChildCount returns number of all children below it.
     * @return int
     */
    public function getChildCount()
    {
        return ($this->getRight() - $this->getLeft() - 1) / 2;
    }

    //
    // Setters
    //

    /**
     * setDepth sets the depth attribute.
     * @return \October\Rain\Database\Model
     */
    public function setDepth()
    {
        $this->getConnection()->transaction(function () {
            $this->reload();

            $level = $this->getLevel();

            $this->newNestedTreeQuery()
                ->where($this->getKeyName(), $this->getKey())
                ->update([$this->getDepthColumnName() => $level])
            ;

            $this->setAttribute($this->getDepthColumnName(), $level);
        });

        return $this;
    }

    /**
     * setDefaultLeftAndRight columns
     * @return void
     */
    public function setDefaultLeftAndRight()
    {
        $highRight = $this
            ->newNestedTreeQuery()
            ->reorder()
            ->orderBy($this->getRightColumnName(), 'desc')
            ->limit(1)
            ->first()
        ;

        $maxRight = 0;
        if ($highRight !== null) {
            $maxRight = $highRight->getRight();
        }

        $this->setAttribute($this->getLeftColumnName(), $maxRight + 1);
        $this->setAttribute($this->getRightColumnName(), $maxRight + 2);
        $this->setAttribute($this->getDepthColumnName(), 0);
    }

    /**
     * resetTreeNesting can be used to repair corrupt or missing tree definitions,
     * it will flatten and heal the necessary columns, all parent and child
     * associations are retained.
     */
    public function resetTreeNesting()
    {
        $this->getConnection()->transaction(function () {
            $buildFunc = function($items, &$nest, $level = 0) use (&$buildFunc) {
                $items->each(function ($item) use (&$nest, $level, $buildFunc) {
                    $item->setAttribute($this->getLeftColumnName(), $nest++);
                    $item->setAttribute($this->getDepthColumnName(), $level);
                    $buildFunc($item->getChildren(), $nest, $level + 1);
                    $item->setAttribute($this->getRightColumnName(), $nest++);
                    $item->save(['force' => true]);
                });
            };

            $records = $this
                ->newNestedTreeQuery()
                ->whereNull($this->getParentColumnName())
                ->get()
            ;

            $nest = 1;
            $buildFunc($records, $nest);
        });
    }

    /**
     * resetTreeOrphans can be used to locate orphaned records, those that refer
     * to a parent_id value where the associated record no longer exists, and
     * promote them to be visible in the collection again, by setting the
     * parent column to null.
     */
    public function resetTreeOrphans()
    {
        $orphanMap = [];
        $recordMap = $this
            ->newNestedTreeQuery()
            ->pluck($this->getParentColumnName(), $this->getKeyName())
            ->all();

        foreach ($recordMap as $id => $parent) {
            if ($parent && !array_key_exists($parent, $recordMap)) {
                $orphanMap[] = $id;
            }
        }

        if ($orphanMap) {
            $this->newNestedTreeQuery()
                ->whereIn($this->getKeyName(), $orphanMap)
                ->update([$this->getParentColumnName() => null]);
        }
    }

    //
    // Moving
    //

    /**
     * moveTo is a handler for all node alignments.
     * @param mixed  $target
     * @param string $position
     * @return \October\Rain\Database\Model
     */
    protected function moveTo($target, $position)
    {
        // Validate target
        if ($target instanceof \October\Rain\Database\Model) {
            $target->reload();
        }
        else {
            $target = $this->resolveMoveTarget($target);
        }

        // Validate move
        if (!$this->validateMove($this, $target, $position)) {
            return $this;
        }

        // Perform move
        $this->getConnection()->transaction(function () use ($target, $position) {
            $this->performMove($this, $target, $position);
        });

        // Reapply alignments
        $target->reload();
        $this->setDepth();

        foreach ($this->newNestedTreeQuery()->allChildren()->get() as $descendant) {
            $descendant->setDepth();
        }

        $this->reload();
        return $this;
    }

    /**
     * performMove executes the SQL query associated with the update of the indexes affected
     * by the move operation.
     * @return int
     */
    protected function performMove($node, $target, $position)
    {
        [$a, $b, $c, $d] = $this->getSortedBoundaries($node, $target, $position);

        $connection = $node->getConnection();
        $grammar = $connection->getQueryGrammar();
        $pdo = $connection->getPdo();

        $parentId = $position === 'child'
            ? $target->getKey()
            : $target->getParentId();

        if ($parentId === null) {
            $parentId = 'NULL';
        }
        else {
            $parentId = $pdo->quote($parentId);
        }

        $currentId = $pdo->quote($node->getKey());
        $leftColumn = $node->getLeftColumnName();
        $rightColumn = $node->getRightColumnName();
        $parentColumn = $node->getParentColumnName();
        $wrappedLeft = $grammar->wrap($leftColumn);
        $wrappedRight = $grammar->wrap($rightColumn);
        $wrappedParent = $grammar->wrap($parentColumn);
        $wrappedId = DbDongle::cast($grammar->wrap($node->getKeyName()), 'TEXT');

        $leftSql = "CASE
            WHEN $wrappedLeft BETWEEN $a AND $b THEN $wrappedLeft + $d - $b
            WHEN $wrappedLeft BETWEEN $c AND $d THEN $wrappedLeft + $a - $c
            ELSE $wrappedLeft END";

        $rightSql = "CASE
            WHEN $wrappedRight BETWEEN $a AND $b THEN $wrappedRight + $d - $b
            WHEN $wrappedRight BETWEEN $c AND $d THEN $wrappedRight + $a - $c
            ELSE $wrappedRight END";

        $parentSql = "CASE
            WHEN $wrappedId = $currentId THEN $parentId
            ELSE $wrappedParent END";

        $result = $node->newNestedTreeQuery()
            ->where(function ($query) use ($leftColumn, $rightColumn, $a, $d) {
                $query
                    ->whereBetween($leftColumn, [$a, $d])
                    ->orWhereBetween($rightColumn, [$a, $d])
                ;
            })
            ->update([
                $leftColumn => $connection->raw($leftSql),
                $rightColumn => $connection->raw($rightSql),
                $parentColumn => $connection->raw($parentSql)
            ])
        ;

        return $result;
    }

    /**
     * resolveMoveTarget
     * @return \October\Rain\Database\Model|null
     */
    protected function resolveMoveTarget($targetId)
    {
        $query = $this->newNestedTreeQuery();

        if (
            $this->isClassInstanceOf(\October\Contracts\Database\MultisiteInterface::class) &&
            $this->isMultisiteEnabled()
        ) {
            return $query->applyOtherSiteRoot($targetId)->first();
        }

        return $query->find($targetId);
    }

    /**
     * validateMove validates a proposed move and returns true if changes are needed.
     * @return void
     */
    protected function validateMove($node, $target, $position)
    {
        if (!$node->exists) {
            throw new Exception('A new node cannot be moved.');
        }

        if (!in_array($position, ['child', 'left', 'right'])) {
            throw new Exception(sprintf(
                'Position should be either child, left, right. Supplied position is "%s".',
                $position
            ));
        }

        if ($target === null) {
            if ($position === 'left' || $position === 'right') {
                throw new Exception(sprintf(
                    'Cannot resolve target node. This node cannot move any further to the %s.',
                    $position
                ));
            }

            throw new Exception('Cannot resolve target node.');
        }

        if ($node === $target) {
            throw new Exception('A node cannot be moved to itself.');
        }

        if ($target->isInsideSubtree($node)) {
            throw new Exception('A node cannot be moved to a descendant of itself.');
        }

        return !(
            $this->getPrimaryBoundary($node, $target, $position) === $node->getRight() ||
            $this->getPrimaryBoundary($node, $target, $position) === $node->getLeft()
        );
    }

    /**
     * getPrimaryBoundary calculates the boundary.
     * @return int
     */
    protected function getPrimaryBoundary($node, $target, $position)
    {
        $primaryBoundary = null;
        switch ($position) {
            case 'child':
                $primaryBoundary = $target->getRight();
                break;

            case 'left':
                $primaryBoundary = $target->getLeft();
                break;

            case 'right':
                $primaryBoundary = $target->getRight() + 1;
                break;
        }

        return ($primaryBoundary > $node->getRight())
            ? $primaryBoundary - 1
            : $primaryBoundary;
    }

    /**
     * getOtherBoundary calculates the other boundary.
     * @return int
     */
    protected function getOtherBoundary($node, $target, $position)
    {
        return ($this->getPrimaryBoundary($node, $target, $position) > $node->getRight())
            ? $node->getRight() + 1
            : $node->getLeft() - 1;
    }

    /**
     * getSortedBoundaries calculates a sorted boundaries array.
     * @return array
     */
    protected function getSortedBoundaries($node, $target, $position)
    {
        $boundaries = [
            $node->getLeft(),
            $node->getRight(),
            $this->getPrimaryBoundary($node, $target, $position),
            $this->getOtherBoundary($node, $target, $position)
        ];

        sort($boundaries);

        return $boundaries;
    }

    //
    // Column getters
    //

    /**
     * getParentColumnName
     * @return string
     */
    public function getParentColumnName()
    {
        return defined('static::PARENT_ID') ? static::PARENT_ID : 'parent_id';
    }

    /**
     * getQualifiedParentColumnName
     * @return string
     */
    public function getQualifiedParentColumnName()
    {
        return $this->getTable(). '.' .$this->getParentColumnName();
    }

    /**
     * getParentId gets value of the model parent_id column.
     * @return int
     */
    public function getParentId()
    {
        return $this->getAttribute($this->getParentColumnName());
    }

    /**
     * getLeftColumnName
     * @return string
     */
    public function getLeftColumnName()
    {
        return defined('static::NEST_LEFT') ? static::NEST_LEFT : 'nest_left';
    }

    /**
     * getQualifiedLeftColumnName
     * @return string
     */
    public function getQualifiedLeftColumnName()
    {
        return $this->getTable() . '.' . $this->getLeftColumnName();
    }

    /**
     * getLeft column value.
     * @return int
     */
    public function getLeft()
    {
        return $this->getAttribute($this->getLeftColumnName());
    }

    /**
     * getRightColumnName
     * @return string
     */
    public function getRightColumnName()
    {
        return defined('static::NEST_RIGHT') ? static::NEST_RIGHT : 'nest_right';
    }

    /**
     * getQualifiedRightColumnName
     * @return string
     */
    public function getQualifiedRightColumnName()
    {
        return $this->getTable() . '.' . $this->getRightColumnName();
    }

    /**
     * getRight column value.
     * @return int
     */
    public function getRight()
    {
        return $this->getAttribute($this->getRightColumnName());
    }

    /**
     * getDepthColumnName
     * @return string
     */
    public function getDepthColumnName()
    {
        return defined('static::NEST_DEPTH') ? static::NEST_DEPTH : 'nest_depth';
    }

    /**
     * getQualifiedDepthColumnName
     * @return string
     */
    public function getQualifiedDepthColumnName()
    {
        return $this->getTable() . '.' . $this->getDepthColumnName();
    }

    /**
     * getDepth column value.
     * @return int
     */
    public function getDepth()
    {
        return $this->getAttribute($this->getDepthColumnName());
    }

    //
    // Instances
    //

    /**
     * newNestedTreeQuery creates a new query for nested sets
     */
    protected function newNestedTreeQuery()
    {
        return $this->newQuery();
    }

    /**
     * newCollection returns a custom TreeCollection collection.
     */
    public function newCollection(array $models = [])
    {
        return new TreeCollection($models);
    }
}
