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
     * Eager loads relationships and joins them to a query
     */
    public function joinWith($relations)
    {
        if (is_string($relations)) $relations = func_get_args();

        foreach ($relations as $index => $relation) {
            if (!$this->model->hasRelation($relation))
                unset($relations[$index]);
        }

        $this->with($relations);

        foreach ($relations as $relation) {
            $relationObj = $this->model->$relation();
            $relationObj->joinWithQuery($this);
        }

        return $this;
    }

    /**
     * Perform a search query
     * @param  string $query  Search query
     * @param  array $columns Table columns to search
     * @return self
     */
    public function searchWhere($query, $columns = [])
    {
        if (!is_array($columns))
            $columns = [$columns];

        $words = explode(' ', $query);
        foreach ($columns as $field) {
            $this->orWhere(function($query) use ($field, $words) {
                foreach ($words as $word) {
                    if (!strlen($word)) continue;
                    $fieldSql = $this->query->raw(sprintf("lower(%s)", $field));
                    $wordSql = '%'.trim(mb_strtolower($word)).'%';
                    $query->where($fieldSql, 'LIKE', $wordSql);
                }
            });
        }

        return $this;
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