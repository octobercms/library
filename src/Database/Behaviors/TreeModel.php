<?php namespace October\Rain\Database\Behaviors;

use Exception;
use October\Rain\Database\ModelBehavior;
use Illuminate\Database\Eloquent\Collection;

/**
 * Act as Tree model extension
 * 
 * Simple category implementation, for advanced implementation see:
 * \October\Rain\Database\Behaviors\NestedSetModel
 *
 * Usage:
 *
 * Model table must have parent_id table column.
 * In the model class definition:
 *
 *   public $implement = ['October.Rain.Database.Behaviors.TreeModel'];
 *
 * To get children:
 *
 *   $model->getChildren();
 *
 * To get root elements:
 *
 *   $model->getRootChildren();
 *
 * You can change the sort field used by declaring:
 *
 *   public $treeModelParentColumn = 'my_parent_column';
 *
 */
class TreeModel extends ModelBehavior
{

    /**
     * @var string The database column that identifies the parent.
     */
    protected $parentColumn = 'parent_id';

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

    private $modelClass;

    /*
     * Constructor
     */
    public function __construct($model)
    {
        parent::__construct($model);

        $this->modelClass = get_class($model);

        if (isset($this->model->treeModelParentColumn))
            $this->parentColumn = $this->model->treeModelParentColumn;
    }

    /**
     * Resets the cached values for all.
     * @return void
     */
    public static function clearCache()
    {
        self::$objectCache = [];
        self::$parentCache = [];
        self::$cacheSortColumn = [];
    }

    /**
     * Returns a list of children records.
     * @param  string $orderBy Specifies a database column name to sort the items by.
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function getChildren($orderBy = 'name')
    {
        if (!$this->cacheExists($orderBy))
            $this->initCache($orderBy);

        $cacheKey = $this->getCacheKey($orderBy);

        if (isset(self::$objectCache[$this->modelClass][$cacheKey][$this->model->id]))
            return new Collection(self::$objectCache[$this->modelClass][$cacheKey][$this->model->id]);

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
    public function getRootChildren($orderBy = 'name')
    {
        if (!$this->cacheExists($orderBy))
            $this->initCache($orderBy);

        $cacheKey = $this->getCacheKey($orderBy);

        if (isset(self::$objectCache[$this->modelClass][$cacheKey][-1]))
            return new Collection(self::$objectCache[$this->modelClass][$cacheKey][-1]);

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
        $children = $this->model->getChildren($orderBy);

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
            $result[] = $parent->{$this->model->treeModelNameColumn};

        return implode($separator, array_reverse($result));
    }

    /**
     * Returns a parent record.
     * @param  string $orderBy Specifies a database column name to sort the items by.
     * @return array
     */
    public function getParent($orderBy = 'name')
    {
        if (!$this->cacheExists($orderBy))
            $this->initCache($orderBy);

        $cacheKey = $this->getCacheKey($orderBy);

        $parentKey = $this->getParentColumnName();
        if (!$this->model->$parentKey)
            return null;

        if (!isset(self::$parentCache[$this->modelClass][$cacheKey][$this->model->$parentKey]))
            return null;

        return self::$parentCache[$this->modelClass][$cacheKey][$this->model->$parentKey];
    }

    /**
     * Returns a list of parent records.
     * @param  string $orderBy Specifies a database column name to sort the items by.
     * @return array
     */
    public function getParents($orderBy = 'name')
    {
        $parent = $this->model->getParent($orderBy);
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
        array_unshift($collection, $this->model);
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
        return $this->parentColumn;
    }

    /**
     * Get fully qualified parent column name.
     * @return string
     */
    public function getQualifiedParentColumnName()
    {
        return $this->model->getTable(). '.' .$this->getParentColumnName();
    }

    /**
     * Get value of the model parent_id column.
     * @return int
     */
    public function getParentId()
    {
        return $this->model->getAttribute($this->getParentColumnName());
    }

    //
    // Cache
    //

    /**
     * Caches all the records needed for viewing parent/child relationships.
     * @param  string $orderBy Specifies a database column name to sort the items by.
     * @return void
     */
    private function initCache($orderBy)
    {
        $className = $this->modelClass;
        $cacheKey = $this->getCacheKey($orderBy);

        $query = $this->model->newQuery();

        $query = $query->orderBy($orderBy);

        if ($this->model->treeModelSqlFilter)
            $query = $query->whereRaw($this->model->treeModelSqlFilter);

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

        self::$objectCache[$this->modelClass][$cacheKey] = $objectCache;
        self::$parentCache[$this->modelClass][$cacheKey] = $parentCache;
        self::$cacheSortColumn[$this->modelClass][$cacheKey] = $orderBy;
    }

    /**
     * Returns a key used for caching
     * @param  string $orderBy
     * @return string
     */
    private function getCacheKey($orderBy)
    {
        return $orderBy . $this->model->treeModelSqlFilter;
    }

    /**
     * Checks if a cache key exists
     * @param  string $orderBy
     * @return boolean
     */
    private function cacheExists($orderBy)
    {
        $cacheKey = $this->getCacheKey($orderBy);
        return array_key_exists($this->modelClass, self::$objectCache) &&
            array_key_exists($cacheKey, self::$objectCache[$this->modelClass]);
    }

}