<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;

trait HasOneOrMany
{
    use DeferOneOrMany;

    /**
     * @var string The "name" of the relationship.
     */
    protected $relationName;

    /**
     * Save the supplied related model with deferred binding support.
     */
    public function save(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            return parent::save($model);
        }

        $this->add($model, $sessionKey);
        return $model->save() ? $model : false;
    }

    /**
     * Alias for the addMany() method.
     * @param  array  $models
     * @return array
     */
    public function saveMany($models, $sessionKey = null)
    {
        $this->addMany($models, $sessionKey);

        return $models;
    }

    /**
     * Create a new instance of this related model with deferred binding support.
     */
    public function create(array $attributes = [], $sessionKey = null)
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
            $model->setAttribute($this->getForeignKeyName(), $this->getParentKey());
            $model->save();

            /*
             * Use the opportunity to set the relation in memory
             */
            if ($this instanceof HasOne) {
                $this->parent->setRelation($this->relationName, $model);
            }
            else {
                $this->parent->reloadRelations($this->relationName);
            }
        }
        else {
            $this->parent->bindDeferred($this->relationName, $model, $sessionKey);
        }
    }

    /**
     * Attach an array of models to the parent instance with deferred binding support.
     * @param  array  $models
     * @return void
     */
    public function addMany($models, $sessionKey = null)
    {
        foreach ($models as $model) {
            $this->add($model, $sessionKey);
        }
    }

    /**
     * Removes a model from this relationship type.
     */
    public function remove(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            $model->setAttribute($this->getForeignKeyName(), null);
            $model->save();

            /*
             * Use the opportunity to set the relation in memory
             */
            if ($this instanceof HasOne) {
                $this->parent->setRelation($this->relationName, null);
            }
            else {
                $this->parent->reloadRelations($this->relationName);
            }
        }
        else {
            $this->parent->unbindDeferred($this->relationName, $model, $sessionKey);
        }
    }

    /**
     * Get the foreign key for the relationship.
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Get the associated "other" key of the relationship.
     * @return string
     */
    public function getOtherKey()
    {
        return $this->localKey;
    }
}
