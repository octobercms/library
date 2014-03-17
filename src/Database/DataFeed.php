<?php namespace October\Rain\Database;

use DB;
use Str;
use Closure;
use Illuminate\Database\Eloquent\Collection;

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
     * @var string The attribute to use for each model class name.
     */
    public $modelVar = 'model_name';

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
     * @var array Model collection pre-query.
     */
    protected $collection = [];

    /**
     * @var Builder Cache containing the generic collection union query.
     */
    private $queryCache;

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

        $this->collection[] = compact('item', 'tag', 'orderBy');

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
        $result = DB::table(DB::raw("(".$query->toSql().") as records"))->select(DB::raw("COUNT(*) as total"))->first();
        return $result->total;
    }

    /**
     * Executes the generic union query and eager loads the results in to the added models
     */
    public function get()
    {
        $query = $this->processCollection();

        /*
         * Apply sorting to the entire query
         * @todo Safe?
         */
        $orderBySql = 'ORDER BY ' . $this->sortVar . ' ' . $this->sortDirection;
        $records = DB::select(DB::raw($query->toSql() . ' ' . $orderBySql), $query->getBindings());

        // $query->limitUnion(3);
        // $query->orderUnionBy($this->sortVar, $this->sortDirection);

        $records = $query->get();

        /*
         * Build a collection of class names and IDs needed
         */
        $mixedArray = [];
        foreach ($records as $record) {
            $className = $record->{$this->modelVar};
            $mixedArray[$className][] = $record->id;
        }

        /*
         * Eager load the data collection
         */
        $collectionArray = [];
        foreach ($mixedArray as $className => $ids) {
            $obj = new $className;
            $collectionArray[$className] = $obj->whereIn('id', $ids)->get();
        }

        /*
         * Now load the data objects in to a final array
         */
        $dataArray = [];
        foreach ($records as $record) {
            $tagName = $record->{$this->tagVar};
            $className = $record->{$this->modelVar};

            $obj = $collectionArray[$className]->find($record->id);
            $obj->{$this->tagVar} = $tagName;
            $obj->{$this->modelVar} = $className;

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
        if ($direction)
            $this->sortDirection = $direction;

        return $this;
    }

    /**
     * Creates a generic union query of each added collection
     */
    private function processCollection()
    {
        if ($this->queryCache !== null)
            return $this->queryCache;

        $lastQuery = null;
        foreach ($this->collection as $data)
        {
            extract($data);
            $cleanQuery = clone $this->getQuery($item);
            $model = $this->getModel($item);
            $class = str_replace('\\', '\\\\', get_class($model));

            $sorting = $model->getTable() . '.';
            $sorting .= $orderBy ?: $this->sortField;

            /*
             * Flush the select, add ID, tag and class
             */
            $cleanQuery = $cleanQuery->select('id');
            $cleanQuery = $cleanQuery->addSelect(DB::raw("(SELECT '".$tag."') as ".$this->tagVar));
            $cleanQuery = $cleanQuery->addSelect(DB::raw("(SELECT '".$class."') as ".$this->modelVar));
            $cleanQuery = $cleanQuery->addSelect(DB::raw("(SELECT ".$sorting.") as ".$this->sortVar));

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
     * Get the model from a builder object
     */
    private function getModel($item)
    {
        return $item->getModel();
    }

    /**
     * Get the query from a builder object
     */
    private function getQuery($item)
    {
        return $item->getQuery();
    }
}