<?php namespace October\Rain\Database\Traits;

use DbDongle;
use October\Rain\Database\Collection;
use October\Rain\Database\TreeCollection;
use October\Rain\Database\NestedTreeScope;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Exception;

/**
 * Nested set model trait
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
 */

trait NestedTree
{
    /**
     * @var int Indicates if the model should be aligned to new parent.
     */
    protected $moveToNewParentId = false;

    /*
     * Constructor
     */
    public static function bootNestedTree()
    {
        static::addGlobalScope(new NestedTreeScope);

        static::extend(function ($model) {
            /*
             * Define relationships
             */
            $model->hasMany['children'] = [
                get_class($model),
                'key' => $model->getParentColumnName()
            ];

            $model->belongsTo['parent'] = [
                get_class($model),
                'key' => $model->getParentColumnName()
            ];

            /*
             * Bind events
             */
            $model->bindEvent('model.beforeCreate', function () use ($model) {
                $model->setDefaultLeftAndRight();
            });

            $model->bindEvent('model.beforeSave', function () use ($model) {
                $model->storeNewParent();
            });

            $model->bindEvent('model.afterSave', function () use ($model) {
                $model->moveToNewParent();
            });

            $model->bindEvent('model.beforeDelete', function () use ($model) {
                $model->deleteDescendants();
            });

            if (static::hasGlobalScope(SoftDeletingScope::class)) {
                $model->bindEvent('model.beforeRestore', function () use ($model) {
                    $model->shiftSiblingsForRestore();
                });

                $model->bindEvent('model.afterRestore', function () use ($model) {
                    $model->restoreDescendants();
                });
            }
        });
    }

    /**
     * Handle if the parent column is modified so it can be realigned.
     * @return void
     */
    public function storeNewParent()
    {
        $parentColumn = $this->getParentColumnName();
        $isDirty = $this->isDirty($parentColumn);

        /**
         * If the model has just been created and the parent ID is not null
         * or if the model exists and the parent ID has changed then we
         * need to move the model in the tree
         */
        if (
            (!$this->exists && $this->getParentId() !== null) ||
            ($this->exists && $isDirty)
        ) {
            $this->moveToNewParentId = $this->getParentId();
        }
    }

    /**
     * If the parent identifier is dirty, realign the nesting.
     * @return void
     */
    public function moveToNewParent()
    {
        $parentId = $this->moveToNewParentId;

        if ($parentId === null) {
            $this->makeRoot();
        }
        elseif ($parentId !== false) {
            $this->makeChildOf($parentId);
        }
    }

    /**
     * Deletes a branch off the tree, shifting all the elements on the right
     * back to the left so the counts work.
     * @return void
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

            /*
             * Delete children
             */
            $this->newQuery()
                ->where($leftCol, '>', $left)
                ->where($rightCol, '<', $right)
                ->delete()
            ;

            /*
             * Update left and right indexes for the remaining nodes
             */
            $diff = $right - $left + 1;

            $this->newQuery()
                ->where($leftCol, '>', $right)
                ->decrement($leftCol, $diff)
            ;

            $this->newQuery()
                ->where($rightCol, '>', $right)
                ->decrement($rightCol, $diff)
            ;
        });
    }

    /**
     * Allocates a slot for the the current node between its siblings.
     * @return void
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

            /*
             * Update left and right indexes for the remaining nodes
             */
            $diff = $right - $left + 1;

            $this->newQuery()
                ->where($leftCol, '>=', $left)
                ->increment($leftCol, $diff)
            ;

            $this->newQuery()
                ->where($rightCol, '>=', $left)
                ->increment($rightCol, $diff)
            ;
        });
    }

    /**
     * Restores all of the current node descendants.
     * @return void
     */
    public function restoreDescendants()
    {
        if ($this->getRight() === null || $this->getLeft() === null) {
            return;
        }

        $this->getConnection()->transaction(function () {
            $this->newQuery()
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
     * Make this model a root node.
     * @return \October\Rain\Database\Model
     */
    public function makeRoot()
    {
        return $this->moveAfter($this->getRoot());
    }

    /**
     * Make model node a child of specified node.
     * @return \October\Rain\Database\Model
     */
    public function makeChildOf($node)
    {
        return $this->moveTo($node, 'child');
    }

    /**
     * Find the left sibling and move to left of it.
     * @return \October\Rain\Database\Model
     */
    public function moveLeft()
    {
        return $this->moveBefore($this->getLeftSibling());
    }

    /**
     * Find the right sibling and move to the right of it.
     * @return \October\Rain\Database\Model
     */
    public function moveRight()
    {
        return $this->moveAfter($this->getRightSibling());
    }

    /**
     * Move to the model to before (left) specified node.
     * @return \October\Rain\Database\Model
     */
    public function moveBefore($node)
    {
        return $this->moveTo($node, 'left');
    }

    /**
     * Move to the model to after (right) a specified node.
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
     * Returns true if this is a root node.
     * @return boolean
     */
    public function isRoot()
    {
        return $this->getParentId() === null;
    }

    /**
     * Returns true if this is a child node.
     * @return boolean
     */
    public function isChild()
    {
        return !$this->isRoot();
    }

    /**
     * Returns true if this is a leaf node (end of a branch).
     * @return boolean
     */
    public function isLeaf()
    {
        return $this->exists && ($this->getRight() - $this->getLeft() === 1);
    }

    /**
     * Checks if the supplied node is inside the subtree of this model.
     * @param \Model
     * @return boolean
     */
    public function isInsideSubtree($node)
    {
        return (
            $this->getLeft() >= $node->getLeft() &&
            $this->getLeft() <= $node->getRight() &&
            $this->getRight() >= $node->getLeft() &&
            $this->getRight() <= $node->getRight()
        );
    }

    /**
     * Returns true if node is a descendant.
     *
     * @param NestedSet
     * @return boolean
     */
    public function isDescendantOf($other)
    {
        return ($this->getLeft() > $other->getLeft() && $this->getLeft() < $other->getRight());
    }

    //
    // Scopes
    //

    /**
     * Query scope which extracts a certain node object from the current query expression.
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeWithoutNode($query, $node)
    {
        return $query->where($node->getKeyName(), '!=', $node->getKey());
    }

    /**
     * Extracts current node (self) from current query expression.
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeWithoutSelf($query)
    {
        return $this->scopeWithoutNode($query, $this);
    }

    /**
     * Extracts first root (from the current node context) from current query expression.
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeWithoutRoot($query)
    {
        return $this->scopeWithoutNode($query, $this->getRoot());
    }

    /**
     * Set of all children & nested children.
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
     * Returns a prepared query with all parents up the tree.
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
     * Filter targeting all children of the parent, except self.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSiblings($query, $includeSelf = false)
    {
        $query->where($this->getParentColumnName(), $this->getParentId());

        return $includeSelf ? $query : $query->withoutSelf();
    }

    /**
     * Returns all final nodes without children.
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
     * Returns a list of all root nodes, without eager loading
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
     * Non chaining scope, returns an eager loaded hierarchy tree. Children are
     * eager loaded inside the $model->children relation.
     * @return Collection A collection
     */
    public function scopeGetNested($query)
    {
        return $query->get()->toNested();
    }

    /**
     * Gets an array with values of a given column. Values are indented according to their depth.
     * @param  string $column Array values
     * @param  string $key    Array keys
     * @param  string $indent Character to indent depth
     * @return array
     */
    public function scopeListsNested($query, $column, $key = null, $indent = '&nbsp;&nbsp;&nbsp;')
    {
        $columns = [$this->getDepthColumnName(), $column];
        if ($key !== null) {
            $columns[] = $key;
        }

        $results = new Collection($query->getQuery()->get($columns));
        $values = $results->pluck($columns[1])->all();
        $indentation = $results->pluck($columns[0])->all();

        if (count($values) !== count($indentation)) {
            throw new Exception('Column mismatch in listsNested method. Are you sure the columns exist?');
        }

        foreach ($values as $_key => $value) {
            $values[$_key] = str_repeat($indent, $indentation[$_key]) . $value;
        }

        if ($key !== null && count($results) > 0) {
            $keys = $results->pluck($key)->all();

            return array_combine($keys, $values);
        }

        return $values;
    }

    //
    // Getters
    //

    /**
     * Returns all nodes and children.
     * @return \October\Rain\Database\Collection
     */
    public function getAll($columns = ['*'])
    {
        return $this->newQuery()->get($columns);
    }

    /**
     * Returns the root node starting from the current node.
     * @return \October\Rain\Database\Model
     */
    public function getRoot()
    {
        if ($this->exists) {
            return $this->newQuery()->parents(true)
                ->where(function ($query) {
                    $query->whereNull($this->getParentColumnName());
                    $query->orWhere($this->getParentColumnName(), 0);
                })
                ->first()
            ;
        }

        $parentId = $this->getParentId();

        if ($parentId !== null && ($currentParent = $this->newQuery()->find($parentId))) {
            return $currentParent->getRoot();
        }

        return $this;
    }

    /**
     * Returns a list of all root nodes, with children eager loaded.
     * @return \October\Rain\Database\Collection
     */
    public function getEagerRoot()
    {
        return $this->newQuery()->getNested();
    }

    /**
     * Returns an array column/key pair of all root nodes, with children eager loaded.
     * @return array
     */
    public function getRootList($column, $key = null, $indent = '&nbsp;&nbsp;&nbsp;')
    {
        return $this->newQuery()->listsNested($column, $key, $indent);
    }

    /**
     * The direct parent node.
     * @return \October\Rain\Database\Collection
     */
    public function getParent()
    {
        return $this->parent()->get();
    }

    /**
     * Returns all parents up the tree.
     * @return \October\Rain\Database\Collection
     */
    public function getParents()
    {
        return $this->newQuery()->parents()->get();
    }

    /**
     * Returns all parents up the tree and self.
     * @return \October\Rain\Database\Collection
     */
    public function getParentsAndSelf()
    {
        return $this->newQuery()->parents(true)->get();
    }

    /**
     * Returns direct child nodes.
     * @return \October\Rain\Database\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Returns direct child nodes, with ->children eager loaded.
     * @return \October\Rain\Database\Collection
     */
    public function getEagerChildren()
    {
        return $this->newQuery()->allChildren()->getNested();
    }

    /**
     * Returns all children down the tree.
     * @return \October\Rain\Database\Collection
     */
    public function getAllChildren()
    {
        return $this->newQuery()->allChildren()->get();
    }

    /**
     * Returns all children and self.
     * @return \October\Rain\Database\Collection
     */
    public function getAllChildrenAndSelf()
    {
        return $this->newQuery()->allChildren(true)->get();
    }

    /**
     * Return all siblings (parent's children).
     * @return \October\Rain\Database\Collection
     */
    public function getSiblings()
    {
        return $this->newQuery()->siblings()->get();
    }

    /**
     * Return all siblings and self.
     * @return \October\Rain\Database\Collection
     */
    public function getSiblingsAndSelf()
    {
        return $this->newQuery()->siblings(true)->get();
    }

    /**
     * Return left sibling
     * @return \October\Rain\Database\Model
     */
    public function getLeftSibling()
    {
        return $this->siblings()->where($this->getRightColumnName(), '=', $this->getLeft() - 1)->first();
    }

    /**
     * Return right sibling
     * @return \October\Rain\Database\Model
     */
    public function getRightSibling()
    {
        return $this->siblings()->where($this->getLeftColumnName(), '=', $this->getRight() + 1)->first();
    }

    /**
     * Returns all final nodes without children.
     * @return \October\Rain\Database\Collection
     */
    public function getLeaves()
    {
        return $this->newQuery()->leaves()->get();
    }

    /**
     * Returns the level of this node in the tree.
     * Root level is 0.
     * @return int
     */
    public function getLevel()
    {
        if ($this->getParentId() === null) {
            return 0;
        }

        return $this->newQuery()->parents()->count();
    }

    /**
     * Returns number of all children below it.
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
     * Sets the depth attribute
     * @return \October\Rain\Database\Model
     */
    public function setDepth()
    {
        $this->getConnection()->transaction(function () {
            $this->reload();

            $level = $this->getLevel();

            $this->newQuery()
                ->where($this->getKeyName(), '=', $this->getKey())
                ->update([$this->getDepthColumnName() => $level])
            ;

            $this->setAttribute($this->getDepthColumnName(), $level);
        });

        return $this;
    }

    /**
     * Set defaults for left and right columns.
     * @return void
     */
    public function setDefaultLeftAndRight()
    {
        $highRight = $this
            ->newQueryWithoutScopes()
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
    }

    //
    // Column getters
    //

    /**
     * Get parent column name.
     * @return string
     */
    public function getParentColumnName()
    {
        return defined('static::PARENT_ID') ? static::PARENT_ID : 'parent_id';
    }

    /**
     * Get fully qualified parent column name.
     * @return string
     */
    public function getQualifiedParentColumnName()
    {
        return $this->getTable(). '.' .$this->getParentColumnName();
    }

    /**
     * Get value of the model parent_id column.
     * @return int
     */
    public function getParentId()
    {
        return $this->getAttribute($this->getParentColumnName());
    }

    /**
     * Get left column name.
     * @return string
     */
    public function getLeftColumnName()
    {
        return defined('static::NEST_LEFT') ? static::NEST_LEFT : 'nest_left';
    }

    /**
     * Get fully qualified left column name.
     * @return string
     */
    public function getQualifiedLeftColumnName()
    {
        return $this->getTable() . '.' . $this->getLeftColumnName();
    }

    /**
     * Get value of the left column.
     * @return int
     */
    public function getLeft()
    {
        return $this->getAttribute($this->getLeftColumnName());
    }

    /**
     * Get right column name.
     * @return string
     */
    public function getRightColumnName()
    {
        return defined('static::NEST_RIGHT') ? static::NEST_RIGHT : 'nest_right';
    }

    /**
     * Get fully qualified right column name.
     * @return string
     */
    public function getQualifiedRightColumnName()
    {
        return $this->getTable() . '.' . $this->getRightColumnName();
    }

    /**
     * Get value of the right column.
     * @return int
     */
    public function getRight()
    {
        return $this->getAttribute($this->getRightColumnName());
    }

    /**
     * Get depth column name.
     * @return string
     */
    public function getDepthColumnName()
    {
        return defined('static::NEST_DEPTH') ? static::NEST_DEPTH : 'nest_depth';
    }

    /**
     * Get fully qualified depth column name.
     * @return string
     */
    public function getQualifiedDepthColumnName()
    {
        return $this->getTable() . '.' . $this->getDepthColumnName();
    }

    /**
     * Get value of the depth column.
     * @return int
     */
    public function getDepth()
    {
        return $this->getAttribute($this->getDepthColumnName());
    }

    //
    // Moving
    //

    /**
     * Handler for all node alignments.
     * @param mixed  $target
     * @param string $position
     * @return \October\Rain\Database\Model
     */
    protected function moveTo($target, $position)
    {
        /*
         * Validate target
         */
        if ($target instanceof \October\Rain\Database\Model) {
            $target->reload();
        }
        else {
            $target = $this->newQuery()->find($target);
        }

        /*
         * Validate move
         */
        if (!$this->validateMove($this, $target, $position)) {
            return $this;
        }

        /*
         * Perform move
         */
        $this->getConnection()->transaction(function () use ($target, $position) {
            $this->performMove($this, $target, $position);
        });

        /*
         * Reapply alignments
         */
        $target->reload();
        $this->setDepth();

        foreach ($this->newQuery()->allChildren()->get() as $descendant) {
            $descendant->setDepth();
        }

        $this->reload();
        return $this;
    }

    /**
     * Executes the SQL query associated with the update of the indexes affected
     * by the move operation.
     * @return int
     */
    protected function performMove($node, $target, $position)
    {
        list($a, $b, $c, $d) = $this->getSortedBoundaries($node, $target, $position);

        $connection = $node->getConnection();
        $grammar = $connection->getQueryGrammar();
        $pdo = $connection->getPdo();

        $parentId = ($position === 'child')
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

        $result = $node->newQuery()
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
     * Validates a proposed move and returns true if changes are needed.
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
     * Calculates the boundary.
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
     * Calculates the other boundary.
     * @return int
     */
    protected function getOtherBoundary($node, $target, $position)
    {
        return ($this->getPrimaryBoundary($node, $target, $position) > $node->getRight())
            ? $node->getRight() + 1
            : $node->getLeft() - 1;
    }

    /**
     * Calculates a sorted boundaries array.
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

    /**
     * Return a custom TreeCollection collection
     */
    public function newCollection(array $models = [])
    {
        return new TreeCollection($models);
    }
}
