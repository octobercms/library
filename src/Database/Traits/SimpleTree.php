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
 * Usage:
 *
 * Model table must have parent_id table column.
 * In the model class definition:
 *
 *   use \October\Rain\Database\Traits\SimpleTree;
 *
 * To get children:
 *
 *   $model->getChildren();
 *
 * To get root elements:
 *
 *   $model->getAllRoot();
 *
 * You can change the sort field used by declaring:
 *
 *   const PARENT_ID = 'my_parent_column';
 *
 */
trait SimpleTree
{

    /**
     * @var string The database column that identifies each item by name.
     */
    public $treeModelNameColumn = 'name';

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
     * @param  string $orderBy Specifies a database column name to sort the items by.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function getChildren($orderBy = 'name')
    {
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
     * Returns a list of root records.
     * @param  string $orderBy Specifies a database column name to sort the items by.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function getAllRoot($orderBy = 'name')
    {
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
     * @param  string $orderBy Specifies a database column name to sort the items by.
     * @return array
     */
    public function getAllChildren($orderBy = 'name')
    {
        $result = [];
        $children = $this->getChildren($orderBy);

        foreach ($children as $child) {
            $result[] = $child;

            $childResult = $child->getAllChildren($orderBy);
            foreach ($childResult as $subChild)
                $result[] = $subChild;
        }

        return $result;
    }

    /**
     * Helper to return the path to the parent as a string
     * @param  string  $separator
     * @param  boolean $includeSelf
     * @param  string  $orderBy
     * @return string
     */
    public function getPath($separator = ' > ', $includeSelf = true, $orderBy = 'name')
    {
        $parents = $this->getParents($includeSelf, $orderBy);
        $parents = array_reverse($parents);

        $result = [];
        foreach ($parents as $parent)
            $result[] = $parent->{$this->treeModelNameColumn};

        return implode($separator, array_reverse($result));
    }

    /**
     * Returns a parent record.
     * @param  string $orderBy Specifies a database column name to sort the items by.
     * @return array
     */
    public function getParent($orderBy = 'name')
    {
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
     * @param  string $orderBy Specifies a database column name to sort the items by.
     * @return array
     */
    public function getParents($orderBy = 'name')
    {
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
     * @param  string $orderBy Specifies a database column name to sort the items by.
     * @return array
     */
    public function getParentsAndSelf($orderBy = 'name')
    {
        $collection = $this->getParents();
        array_unshift($collection, $this);
        return $collection;
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

        $query = $query->orderBy($orderBy);

        if ($this->treeModelSqlFilter)
            $query = $query->whereRaw($this->treeModelSqlFilter);

        $records = $query->get();
        $objectCache = [];
        $parentCache = [];

        $parentKey = $this->getParentColumnName();
        foreach ($records as $record) {
            $parentId = $record->$parentKey !== null ? $record->$parentKey : -1;

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
        return $orderBy . $this->treeModelSqlFilter;
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
     * Return a custom TreeCollection collection
     */
    public function newCollection(array $models = [])
    {
        return new TreeCollection($models);
    }

    /** @deprecated Remove this if year >= 2015 */
    public function getRootChildren($orderBy = 'name') { return $this->getAllRoot($orderBy); }

}