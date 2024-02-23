<?php namespace October\Rain\Database\Traits;

use October\Rain\Database\Collection;
use October\Rain\Database\TreeCollection;
use Exception;

/**
 * SimpleTree model trait
 *
 * Simple tree implementation, for advanced implementation see:
 * October\Rain\Database\Traits\NestedTree
 *
 * SimpleTree is the bare minimum needed for tree functionality, the
 * methods defined here should be implemented by all "tree" traits.
 *
 * Usage:
 *
 * Model table must have parent_id table column.
 * In the model class definition:
 *
 *   use \October\Rain\Database\Traits\SimpleTree;
 *
 * General access methods:
 *
 *   $model->getChildren(); // Returns children of this node
 *   $model->getChildCount(); // Returns number of all children.
 *   $model->getAllChildren(); // Returns all children of this node
 *   $model->getAllRoot(); // Returns all root level nodes (eager loaded)
 *   $model->getAll(); // Returns everything in correct order.
 *
 * Query builder methods:
 *
 *   $query->listsNested(); // Returns an indented array of key and value columns.
 *
 * You can change the sort field used by declaring:
 *
 *   const PARENT_ID = 'my_parent_column';
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait SimpleTree
{
    /**
     * initializeSimpleTree constructor
     */
    public function initializeSimpleTree()
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
    }

    /**
     * getAll returns all nodes and children.
     * @return \October\Rain\Database\Collection
     */
    public function getAll()
    {
        $collection = [];
        foreach ($this->getAllRoot() as $rootNode) {
            $collection[] = $rootNode;
            $collection = $collection + $rootNode->getAllChildren()->getDictionary();
        }

        return new Collection($collection);
    }

    /**
     * getAllChildren gets a list of children records, with their children (recursive)
     * @return \October\Rain\Database\Collection
     */
    public function getAllChildren()
    {
        $result = [];
        $children = $this->getChildren();

        foreach ($children as $child) {
            $result[] = $child;

            $childResult = $child->getAllChildren();
            foreach ($childResult as $subChild) {
                $result[] = $subChild;
            }
        }

        return new Collection($result);
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
     * getChildCount returns number of all children below it.
     * @return int
     */
    public function getChildCount()
    {
        return count($this->getAllChildren());
    }

    /**
     * getParents returns an array of parents, this is a heavy query and can produce
     * in multiple queries.
     */
    public function getParents()
    {
        $result = [];

        $parent = $this;
        $result[] = $parent;

        while ($parent = $parent->parent) {
            $result[] = $parent;
        }

        return array_reverse($result);
    }

    //
    // Scopes
    //

    /**
     * scopeGetAllRoot returns a list of all root nodes, without eager loading.
     * @return \October\Rain\Database\Collection
     */
    public function scopeGetAllRoot($query)
    {
        return $query->where($this->getParentColumnName(), null)->get();
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
        $idName = $this->getKeyName();
        $parentName = $this->getParentColumnName();

        $columns = [$idName, $parentName, $column];
        if ($key !== null) {
            $columns[] = $key;
        }

        $collection = $query->getQuery()->get($columns);

        // Assign all child nodes to their parents
        $pairMap = [];
        $rootItems = [];
        foreach ($collection as $record) {
            if ($parentId = $record->{$parentName}) {
                if (!isset($pairMap[$parentId])) {
                    $pairMap[$parentId] = [];
                }
                $pairMap[$parentId][] = $record;
            }
            else {
                $rootItems[] = $record;
            }
        }

        // Recursive helper function
        $buildCollection = function(
            $items,
            $map,
            $depth = 0
        ) use (
            &$buildCollection,
            $column,
            $key,
            $indent,
            $idName
        ) {
            $result = [];

            $indentString = str_repeat($indent, $depth);

            foreach ($items as $item) {
                if (!property_exists($item, $column)) {
                    throw new Exception('Column mismatch in listsNested method. Are you sure the columns exist?');
                }

                if ($key !== null) {
                    $result[$item->{$key}] = $indentString . $item->{$column};
                }
                else {
                    $result[] = $indentString . $item->{$column};
                }

                // Add the children
                $childItems = array_get($map, $item->{$idName}, []);
                if (count($childItems) > 0) {
                    $result = $result + $buildCollection($childItems, $map, $depth + 1);
                }
            }

            return $result;
        };

        // Build a nested collection
        return $buildCollection($rootItems, $pairMap);
    }

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
     * newCollection returns a custom TreeCollection collection.
     */
    public function newCollection(array $models = [])
    {
        return new TreeCollection($models);
    }
}
