<?php namespace October\Rain\Database;

use Illuminate\Database\Query\Builder as QueryBuilderBase;

/**
 * QueryBuilder restores some features that were removed from base, it also
 * adds some new ones
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class QueryBuilder extends QueryBuilderBase
{
    /**
     * Get an array with the values of a given column.
     *
     * @param  string  $column
     * @param  string|null  $key
     * @return array
     */
    public function lists($column, $key = null)
    {
        return $this->pluck($column, $key)->all();
    }

    /**
     * Retrieve the "count" result of the query,
     * also strips off any orderBy clause.
     *
     * @param  string  $columns
     * @return int
     */
    public function count($columns = '*')
    {
        $previousOrders = $this->orders;

        $this->orders = null;

        $result = parent::count($columns);

        $this->orders = $previousOrders;

        return $result;
    }
}
