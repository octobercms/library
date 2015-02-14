<?php namespace October\Rain\Database\Traits;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use October\Rain\Database\TreeCollection;

/**
 * Simple Tree model trait
 * 
 * Simple category implementation, for advanced implementation see:
 * October\Rain\Database\Traits\NestedTree
 *
 * SimpleTree is useful for providing an orderBy column on the fly,
 * whereas NestedTree has a fixed structure.
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
 *   $model->getAllChildren(); // Returns all children of this node
 *   $model->getAllRoot(); // Returns all root level nodes
 *   $model->getAll(); // Returns everything in correct order.
 *
 * To supply an order column:
 *
 *   $model->orderBy('sort_order')->getAllRoot();
 *
 * Query builder methods:
 *
 *   $query->listsNested(); // Returns an indented array of key and value columns.
 *
 * You can change the sort field used by declaring:
 *
 *   const PARENT_ID = 'my_parent_column';
 *
 * You can change the database column that identifies each item by name:
 *
 *   const TREE_LABEL = 'my_name';
 *
 */
trait SimpleTree
{

    /**
     * @var array The active ordering column for this model.
     */
    public $treeModelActiveOrderBy = null;

    /**
     * @var string The database column that identifies each item by name.
     */
    public $treeModelSqlFilter = null;

    private static $objectCache = [];
    private static $parentCache = [];
    private static $cacheSortColumn = [];

    /**
     * Resets the cached values for all.
     * @return void
     */
    public static function clearTreeCache()
    {
        static::$objectCache = [];
        static::$parentCache = [];
        static::$cacheSortColumn = [];
    }

    /**
     * Returns a list of children records.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function getChildren()
    {
        $orderBy = $this->getTreeOrderBy();
        $class = get_called_class();

        if (!$this->cacheExists($orderBy))
            $this->initCache($orderBy);

        $cacheKey = $this->getCacheKey($orderBy);

        if (isset(static::$objectCache[$class][$cacheKey][$this->id]))
            return new Collection(static::$objectCache[$class][$cacheKey][$this->id]);

        return new Collection();
    }

    /**
     * Returns number of all children below it.
     * @return int
     */
    public function getChildCount()
    {
        return count($this->getAllChildren());
    }

    /**
     * Returns all nodes and children.
     * @return Illuminate\Database\Eloquent\Collection
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
     * Returns a list of root records.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function getAllRoot()
    {
        $orderBy = $this->getTreeOrderBy();
        $class = get_called_class();

        if (!$this->cacheExists($orderBy))
            $this->initCache($orderBy);

        $cacheKey = $this->getCacheKey($orderBy);

        if (isset(static::$objectCache[$class][$cacheKey][-1]))
            return new Collection(static::$objectCache[$class][$cacheKey][-1]);

        return new Collection();
    }

    /**
     * Get a list of children records, with their children (recursive)
     * @return array
     */
    public function getAllChildren()
    {
        $orderBy = $this->getTreeOrderBy();
        $result = [];
        $children = $this->getChildren($orderBy);

        foreach ($children as $child) {
            $result[] = $child;

            $childResult = $child->getAllChildren($orderBy);
            foreach ($childResult as $subChild)
                $result[] = $subChild;
        }

        return new Collection($result);
    }

    /**
     * Helper to return the path to the parent as a string
     * @param  string  $separator
     * @param  boolean $includeSelf
     * @param  string  $orderBy
     * @return string
     */
    public function getPath($separator = ' > ', $includeSelf = true)
    {
        $orderBy = $this->getTreeOrderBy();
        $parents = $this->getParents($includeSelf);
        $parents = array_reverse($parents);

        $labelColumn = $this->getLabelColumnName();

        $result = [];
        foreach ($parents as $parent)
            $result[] = $parent->{$labelColumn};

        return implode($separator, array_reverse($result));
    }

    /**
     * Returns a parent record.
     * @return array
     */
    public function getParent()
    {
        $orderBy = $this->getTreeOrderBy();
        $class = get_called_class();

        if (!$this->cacheExists($orderBy))
            $this->initCache($orderBy);

        $cacheKey = $this->getCacheKey($orderBy);

        $parentKey = $this->getParentColumnName();
        if (!$this->$parentKey)
            return null;

        if (!isset(static::$parentCache[$class][$cacheKey][$this->$parentKey]))
            return null;

        return static::$parentCache[$class][$cacheKey][$this->$parentKey];
    }

    /**
     * Returns a list of parent records.
     * @return array
     */
    public function getParents()
    {
        $orderBy = $this->getTreeOrderBy();
        $parent = $this->getParent($orderBy);
        $result = [];

        while ($parent != null) {
            $result[] = $parent;
            $parent = $parent->getParent($orderBy);
        }

        return array_reverse($result);
    }

    /**
     * Returns a list of parent records, including the current record.
     * @return array
     */
    public function getParentsAndSelf()
    {
        $orderBy = $this->getTreeOrderBy();
        $collection = $this->getParents();
        array_unshift($collection, $this);
        return $collection;
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
        /*
         * Recursive helper function
         */
        $buildCollection = function($items, $depth = 0) use (&$buildCollection, $column, $key, $indent) {
            $result = [];

            $indentString = str_repeat($indent, $depth);

            foreach ($items as $item) {
                if ($key !== null)
                    $result[$item->{$key}] = $indentString . $item->{$column};
                else
                    $result[] = $indentString . $item->{$column};

                /*
                 * Add the children
                 */
                $childItems = $item->getChildren();
                if ($childItems->count() > 0) {
                    $result = $result + $buildCollection($childItems, $depth + 1);
                }
            }

            return $result;
        };

        /*
         * Build a nested collection
         */
        $model = $query->getModel();
        $rootItems = $model->getAllRoot();
        $result = $buildCollection($rootItems);
        return $result;
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
     * Get label column name.
     * @return string
     */
    public function getLabelColumnName()
    {
        return defined('static::TREE_LABEL') ? static::TREE_LABEL : 'name';
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

    //
    // Cache
    //

    /**
     * Caches all the records needed for viewing parent/child relationships.
     * @param  string $orderBy Specifies a database column name to sort the items by.
     * @return void
     */
    protected function initCache($orderBy)
    {
        $class = get_called_class();
        $cacheKey = $this->getCacheKey($orderBy);

        $query = $this->newQuery();

        list($order, $direction) = $orderBy;
        $query = $query->orderBy($order, $direction);

        if ($this->treeModelSqlFilter)
            $query = $query->whereRaw($this->treeModelSqlFilter);

        $records = $query->get();
        $objectCache = [];
        $parentCache = [];

        $parentKey = $this->getParentColumnName();
        foreach ($records as $record) {
            $parentId = $record->{$parentKey} !== null ? $record->{$parentKey} : -1;

            $record->setTreeOrderBy($order, $direction);

            if (!isset($objectCache[$parentId]))
                $objectCache[$parentId] = [];

            $objectCache[$parentId][] = $record;
            $parentCache[$record->id] = $record;
        }

        static::$objectCache[$class][$cacheKey] = $objectCache;
        static::$parentCache[$class][$cacheKey] = $parentCache;
        static::$cacheSortColumn[$class][$cacheKey] = $orderBy;
    }

    /**
     * Returns a key used for caching
     * @param  string $orderBy
     * @return string
     */
    protected function getCacheKey($orderBy)
    {
        return implode('-', (array) $orderBy) . $this->treeModelSqlFilter;
    }

    /**
     * Checks if a cache key exists
     * @param  string $orderBy
     * @return boolean
     */
    protected function cacheExists($orderBy)
    {
        $class = get_called_class();
        $cacheKey = $this->getCacheKey($orderBy);
        return array_key_exists($class, static::$objectCache) &&
            array_key_exists($cacheKey, static::$objectCache[$class]);
    }

    /**
     * Sets the tree model SQL filter statement.
     * @param string $rawSql
     */
    public function setTreeSqlFilter($rawSql)
    {
        $this->treeModelSqlFilter = $rawSql;
        return $this;
    }

    /**
     * Sets the ordering column and direction for this instance.
     * @param string $order
     * @param string $direction
     */
    public function setTreeOrderBy($order, $direction = 'asc')
    {
        $this->treeModelActiveOrderBy = [$order, $direction];
        return $this;
    }

    /**
     * Returns the column used for ordering the tree children.
     * @return string
     */
    public function getTreeOrderBy()
    {
        if ($this->treeModelActiveOrderBy !== null)
            return $this->treeModelActiveOrderBy;

        return $this->treeModelActiveOrderBy = [$this->getLabelColumnName(), 'asc'];
    }

    /**
     * Return a custom TreeCollection collection
     */
    public function newCollection(array $models = [])
    {
        return new TreeCollection($models);
    }

}