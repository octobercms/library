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