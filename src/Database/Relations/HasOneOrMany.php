<?php namespace October\Rain\Database\Relations;

use Illuminate\Support\Facades\Db;
use Illuminate\Database\Eloquent\Model;

trait HasOneOrMany
{
    use DeferOneOrMany;

    /**
     * Save the supplied related model with deferred binding support.
     */
    public function save(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            return parent::save($model);
        }
        else {
            $this->add($model, $sessionKey);

            // Save the related model and any deferred bindings it might have
            return $model->save(null, $sessionKey) ? $model : false;
        }
    }

    /**
     * Create a new instance of this related model with deferred binding support.
     */
    public function create(array $attributes, $sessionKey = null)
    {
        $model = parent::create($attributes);

        if ($sessionKey !== null)
            $this->add($model, $sessionKey);

        return $model;
    }

    /**
     * Adds a model to this relationship type.
     */
    public function add(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            $model->setAttribute($this->getPlainForeignKey(), $this->parent->getKey());
            $model->save();
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
            $model->setAttribute($this->getPlainForeignKey(), null);
            $model->save();
        }
        else {
            $this->parent->unbindDeferred($this->relationName, $model, $sessionKey);
        }
    }

    /**
     * Joins the relationship tables to a query as a LEFT JOIN.
     */
    public function joinWithQuery($query)
    {
        $query = $query ?: $this->query;

        /*
         * Join the 'other' relation table
         */
        $otherTable = $this->related->getTable();
        $otherKey = $this->parent->getTable().'.'.$this->related->getKeyName();
        $query->leftJoin($otherTable, $this->foreignKey, '=', $otherKey);

        return $this;
    }
}