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
        /**
         * @event model.relation.beforeSave
         * Called before saving a relation (only for HasOneOrMany relation)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.beforeSave', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
         *         TODO: add example code
         *     });
         *
         */
        if ($this->parent->fireEvent('model.relation.beforeSave', [$this->relationName, $this->related], true) === false) {
            return;
        }

        if ($sessionKey === null) {
            return parent::save($model);
        }

        $this->add($model, $sessionKey);
        $result = $model->save() ? $model : false;

        /**
         * @event model.relation.afterSave
         * Called after saving a relation (only for HasOneOrMany relation)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.afterSave', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
         *         TODO: add example code
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.afterSave', [$this->relationName, $this->related]);

        return $result;
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
        /**
         * @event model.relation.beforeCreate
         * Called before creating a new relation between models (only for HasOneOrMany relation)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.beforeCreate', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
         *         TODO: add example code
         *     });
         *
         */
        if ($this->parent->fireEvent('model.relation.beforeCreate', [$this->relationName, $this->related], true) === false) {
            return;
        }

        $model = parent::create($attributes);

        if ($sessionKey !== null) {
            $this->add($model, $sessionKey);
        }

        /**
         * @event model.relation.afterCreate
         * Called after creating a new relation between models (only for HasOneOrMany relation)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.afterCreate', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
         *         TODO: add example code
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.afterCreate', [$this->relationName, $this->related]);

        return $model;
    }

    /**
     * Adds a model to this relationship type.
     */
    public function add(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            $model->setAttribute($this->getForeignKeyName(), $this->getParentKey());

            if (!$model->exists || $model->isDirty()) {
                $model->save();
            }

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
