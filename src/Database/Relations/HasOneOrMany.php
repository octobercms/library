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
            return $model->save() ? $model : false;
        }
    }

    /**
     * Attach an array of models to the parent instance with deferred binding support.
     *
     * @param  array  $models
     * @return array
     */
    public function saveMany(array $models, $sessionKey = null)
    {
        foreach ($models as $model) {
            $this->save($model, $sessionKey);
        }

        return $models;
    }

    /**
     * Create a new instance of this related model with deferred binding support.
     */
    public function create(array $attributes, $sessionKey = null)
    {
        $model = parent::create($attributes);

        if ($sessionKey !== null) {
            $this->add($model, $sessionKey);
        }

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

            /*
             * Use the opportunity to set the relation in memory
             */
            if ($this instanceof HasOne) {
                $this->parent->setRelation($this->relationName, $model);
            }
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

            /*
             * Use the opportunity to set the relation in memory
             */
            if ($this instanceof HasOne) {
                $this->parent->setRelation($this->relationName, null);
            }
        }
        else {
            $this->parent->unbindDeferred($this->relationName, $model, $sessionKey);
        }
    }

}