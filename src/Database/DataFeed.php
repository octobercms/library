<?php namespace October\Rain\Database;

use Db;
use Str;
use Closure;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Exception;

/**
 * Model Data Feed class.
 *
 * Combine various models in to a single feed.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class DataFeed
{

    /**
     * @var string The attribute to use for each model tag name.
     */
    public $tagVar = 'tag_name';

    /**
     * @var string An alias to use for each entries timestamp attribute.
     */
    public $sortVar = 'order_by_column_name';

    /**
     * @var string Default sorting attribute.
     */
    public $sortField = 'id';

    /**
     * @var string Default sorting direction.
     */
    public $sortDirection = 'desc';

    /**
     * @var string Limits the number of results.
     */
    public $limitCount = null;

    /**
     * @var string Set the limit offset.
     */
    public $limitOffset = null;

    /**
     * @var array Model collection pre-query.
     */
    protected $collection = [];

    /**
     * @var Builder Cache containing the generic collection union query.
     */
    protected $queryCache;

    /**
     * @var bool
     */
    public $removeDuplicates = false;

    /**
     * Add a new Builder to the feed collection
     */
    public function add($tag, $item, $orderBy = null)
    {
        if ($item instanceof Closure) {
            $item = call_user_func($item);
        }

        if (!$item)
            return;

        $keyName = $item instanceof EloquentModel
            ? $item->getKeyName()
            : $item->getModel()->getKeyName();

        $this->collection[$tag] = compact('item', 'orderBy', 'keyName');

        // Reset the query cache
        $this->queryCache = null;

        return $this;
    }

    /**
     * Count the number of results from the generic union query
     */
    public function count()
    {
        $query = $this->processCollection();
        $result = Db::table(Db::raw("(".$query->toSql().") as records"))->select(Db::raw("COUNT(*) as total"))->first();
        return $result->total;
    }

    /**
     * Executes the generic union query and eager loads the results in to the added models
     */
    public function get()
    {
        $query = $this->processCollection();

        /*
         * Apply constraints to the entire query
         */
        $query->limit($this->limitCount);

        if ($this->limitOffset) {
            $query->offset($this->limitOffset);
        }

        $query->orderBy($this->sortVar, $this->sortDirection);

        $records = $query->get();

        /*
         * Build a collection of class names and IDs needed
         */
        $mixedArray = [];
        foreach ($records as $record) {
            $tagName = $record->{$this->tagVar};
            $mixedArray[$tagName][] = $record->id;
        }

        /*
         * Eager load the data collection
         */
        $collectionArray = [];
        foreach ($mixedArray as $tagName => $ids) {
            $obj = $this->getModelByTag($tagName);
            $keyName = $this->getKeyNameByTag($tagName);
            $collectionArray[$tagName] = $obj->whereIn($keyName, $ids)->get();
        }

        /*
         * Now load the data objects in to a final array
         */
        $dataArray = [];
        foreach ($records as $record) {
            $tagName = $record->{$this->tagVar};

            $obj = $collectionArray[$tagName]->find($record->id);
            $obj->{$this->tagVar} = $tagName;

            $dataArray[] = $obj;
        }

        return new Collection($dataArray);
    }

    /**
     * Returns the SQL expression used in the generic union
     */
    public function toSql()
    {
        $query = $this->processCollection();
        return $query->toSql();
    }

    /**
     * Sets the default sorting field and direction.
     */
    public function orderBy($field, $direction = null)
    {
        $this->sortField = $field;
        if ($direction) {
            $this->sortDirection = $direction;
        }

        return $this;
    }

    /**
     * Limits the number of results displayed.
     */
    public function limit($count, $offset = null)
    {
        $this->limitCount = $count;
        if ($offset) {
            $this->limitOffset = $offset;
        }

        return $this;
    }

    //
    // Internals
    //

    /**
     * Creates a generic union query of each added collection
     */
    protected function processCollection()
    {
        if ($this->queryCache !== null)
            return $this->queryCache;

        $lastQuery = null;
        foreach ($this->collection as $tag => $data)
        {
            extract($data);
            $cleanQuery = clone $item->getQuery();
            $model = $item->getModel();

            $sorting = $model->getTable() . '.';
            $sorting .= $orderBy ?: $this->sortField;

            /*
             * Flush the select, add ID and tag
             */
            $cleanQuery = $cleanQuery->select(Db::raw($keyName." as id"));
            $cleanQuery = $cleanQuery->addSelect(Db::raw("(SELECT '".$tag."') as ".$this->tagVar));
            $cleanQuery = $cleanQuery->addSelect(Db::raw("(SELECT ".$sorting.") as ".$this->sortVar));

            /*
             * Union this query with the previous one
             */
            if ($lastQuery) {
                if ($this->removeDuplicates)
                    $cleanQuery = $lastQuery->union($cleanQuery);
                else
                    $cleanQuery = $lastQuery->unionAll($cleanQuery);
            }

            $lastQuery = $cleanQuery;
        }

        return $this->queryCache = $lastQuery;
    }

    /**
     * Returns a prepared model by its tag name.
     * @return Model
     */
    protected function getModelByTag($tag)
    {
        extract($this->getDataByTag($tag));
        return $item;
    }

    /**
     * Returns a model key name by its tag name.
     * @return Model
     */
    protected function getKeyNameByTag($tag)
    {
        extract($this->getDataByTag($tag));
        return $keyName;
    }

    /**
     * Returns a data stored about an item by its tag name.
     * @return array
     */
    protected function getDataByTag($tag)
    {
        if (!$data = array_get($this->collection, $tag)) {
            throw new Exception('Unable to find model in collection with tag: '. $tag);
        }

        return $data;
    }
}