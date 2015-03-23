<?php namespace October\Rain\Database\Relations;

use Illuminate\Support\Facades\Db;
use Illuminate\Database\Eloquent\Model;

trait MorphOneOrMany
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
            $model->setAttribute($this->getPlainMorphType(), $this->morphClass);
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

            $options = $this->parent->getRelationDefinition($this->relationName);

            if (array_get($options, 'delete', false)) {
                $model->delete();
            }
            else {
                // Make this model an orphan ;~(
                $model->setAttribute($this->getPlainForeignKey(), null);
                $model->setAttribute($this->getPlainMorphType(), null);
                $model->save();
            }

        }
        else {
            $this->parent->unbindDeferred($this->relationName, $model, $sessionKey);
        }
    }

}