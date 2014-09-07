<?php namespace October\Rain\Database;

use Illuminate\Database\Eloquent\Builder as BuilderModel;

/**
 * Query builder class.
 *
 * Extends Eloquent builder class.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class Builder extends BuilderModel
{

    /**
     * Perform a search on this query for term found in columns.
     * @param  string $term  Search query
     * @param  array $columns Table columns to search
     * @return self
     */
    public function searchWhere($term, $columns = [], $boolean = 'and')
    {
        if (!is_array($columns))
            $columns = [$columns];

        $words = explode(' ', $term);
        $this->where(function($query) use ($columns, $words) {
            foreach ($columns as $field) {
                $query->orWhere(function($query) use ($field, $words) {
                    foreach ($words as $word) {
                        if (!strlen($word)) continue;
                        $fieldSql = $this->query->raw(sprintf("lower(%s)", $field));
                        $wordSql = '%'.trim(mb_strtolower($word)).'%';
                        $query->where($fieldSql, 'LIKE', $wordSql);
                    }
                });
            }
        }, null, null, $boolean);

        return $this;
    }

    /**
     * Add an "or search where" clause to the query.
     * @param  string $term  Search query
     * @param  array $columns Table columns to search
     * @return self
     */
    public function orSearchWhere($term, $columns = [])
    {
        return $this->searchWhere($term, $columns, 'or');
    }

    /**
     * Dynamically handle calls into the query instance.
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($this->model->methodExists($scope = 'scope'.ucfirst($method))) {
            return $this->callScope($scope, $parameters);
        }

        return parent::__call($method, $parameters);
    }

}