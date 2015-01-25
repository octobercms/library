<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany as BelongsToManyBase;
use Illuminate\Database\Eloquent\Model;

class BelongsToMany extends BelongsToManyBase
{

    use DeferOneOrMany;

    /**
     * Save the supplied related model with deferred binding support.
     */
    public function save(Model $model, array $pivotData = [], $sessionKey = null)
    {
        $model->save();
        $this->add($model, $sessionKey, $pivotData);
        return $model;
    }

    /**
     * Create a new instance of this related model with deferred binding support.
     */
    public function create(array $attributes, array $pivotData = [], $sessionKey = null)
    {
        $model = $this->related->create($attributes);

        $this->add($model, $sessionKey, $pivotData);

        return $model;
    }

    /**
     * Adds a model to this relationship type.
     */
    public function add(Model $model, $sessionKey = null, $pivotData = [])
    {
        if ($sessionKey === null) {
            $this->attach($model->getKey(), $pivotData);
        }
        else {
            $this->parent->bindDeferred($this->relationName, $model, $sessionKey);
        }
    }

    /**
     * Removes a model from this relationship type.
     */
    public function remove(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            $this->detach($model->getKey());
        }
        else {
            $this->parent->unbindDeferred($this->relationName, $model, $sessionKey);
        }
    }

    /**
     * Set the left join clause for the relation query, used by DeferOneOrMany.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null
     * @return $this
     */
    protected function setLeftJoin($query = null)
    {
        $query = $query ?: $this->query;

        $baseTable = $this->related->getTable();

        $key = $baseTable.'.'.$this->related->getKeyName();

        $query->leftJoin($this->table, $key, '=', $this->getOtherKey());

        return $this;
    }

}
