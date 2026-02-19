<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * HasOneOrMany
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasOneOrMany
{
    use DeferOneOrMany;

    /**
     * @var string relationName is the "name" of the relationship.
     */
    protected $relationName;

    /**
     * save the supplied related model with deferred binding support.
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
     * saveQuietly saves the supplied related model without raising any events,
     * with deferred binding support.
     */
    public function saveQuietly(Model $model, $sessionKey = null)
    {
        return Model::withoutEvents(function () use ($model, $sessionKey) {
            return $this->save($model, $sessionKey);
        });
    }

    /**
     * saveMany is an alias for the addMany() method
     * @param  array  $models
     * @return array
     */
    public function saveMany($models, $sessionKey = null)
    {
        $this->addMany($models, $sessionKey);

        return $models;
    }

    /**
     * saveManyQuietly saves multiple models without raising any events,
     * with deferred binding support.
     */
    public function saveManyQuietly($models, $sessionKey = null)
    {
        return Model::withoutEvents(function () use ($models, $sessionKey) {
            return $this->saveMany($models, $sessionKey);
        });
    }

    /**
     * create a new instance of this related model with deferred binding support
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
     * createQuietly creates a new instance without raising any events,
     * with deferred binding support.
     */
    public function createQuietly(array $attributes = [], $sessionKey = null)
    {
        return Model::withoutEvents(function () use ($attributes, $sessionKey) {
            return $this->create($attributes, $sessionKey);
        });
    }

    /**
     * forceCreateQuietly creates a new instance bypassing mass assignment
     * without raising any events, with deferred binding support.
     */
    public function forceCreateQuietly(array $attributes = [], $sessionKey = null)
    {
        return Model::withoutEvents(function () use ($attributes, $sessionKey) {
            $model = parent::forceCreate($attributes);

            if ($sessionKey !== null) {
                $this->add($model, $sessionKey);
            }

            return $model;
        });
    }

    /**
     * createMany creates multiple related models with deferred binding support.
     */
    public function createMany(iterable $records, $sessionKey = null)
    {
        $instances = parent::createMany($records);

        if ($sessionKey !== null) {
            foreach ($instances as $model) {
                $this->add($model, $sessionKey);
            }
        }

        return $instances;
    }

    /**
     * createManyQuietly creates multiple models without raising any events,
     * with deferred binding support.
     */
    public function createManyQuietly(iterable $records, $sessionKey = null)
    {
        return Model::withoutEvents(function () use ($records, $sessionKey) {
            return $this->createMany($records, $sessionKey);
        });
    }

    /**
     * createOrFirst attempts to create the record, or if a unique constraint
     * violation occurs, finds the existing record.
     */
    public function createOrFirst(array $attributes = [], \Closure|array $values = [], $sessionKey = null)
    {
        $model = parent::createOrFirst($attributes, $values);

        if ($sessionKey !== null) {
            $this->add($model, $sessionKey);
        }

        return $model;
    }

    /**
     * add a model to this relationship type
     */
    public function add(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            /**
             * @event model.relation.beforeAdd
             * Called before adding a relation to the model (for AttachOneOrMany, HasOneOrMany & MorphOneOrMany relations)
             *
             * Example usage:
             *
             *     $model->bindEvent('model.relation.beforeAdd', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
             *         if ($relationName === 'some_relation') {
             *             return false;
             *         }
             *     });
             *
             */
            if ($this->parent->fireEvent('model.relation.beforeAdd', [$this->relationName, $model], true) === false) {
                return;
            }

            // Associate the model
            if ($this->parent->exists) {
                $model->setAttribute($this->getForeignKeyName(), $this->getParentKey());
                $model->save();
            }
            else {
                $this->parent->bindEventOnce('model.afterSave', function () use ($model) {
                    $model->setAttribute($this->getForeignKeyName(), $this->getParentKey());
                    $model->save();
                });
            }

            // Use the opportunity to set the relation in memory
            if ($this instanceof HasOne) {
                $this->parent->setRelation($this->relationName, $model);
            }
            else {
                $this->parent->unsetRelation($this->relationName);
            }

            /**
             * @event model.relation.add
             * Called after adding a relation to the model (for AttachOneOrMany, HasOneOrMany & MorphOneOrMany relations)
             *
             * Example usage:
             *
             *     $model->bindEvent('model.relation.add', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
             *         $relatedClass = get_class($relatedModel);
             *         $modelClass = get_class($model);
             *         traceLog("{$relatedClass} was added as {$relationName} to {$modelClass}.");
             *     });
             *
             */
            $this->parent->fireEvent('model.relation.add', [$this->relationName, $model]);
        }
        else {
            $this->parent->bindDeferred($this->relationName, $model, $sessionKey);
        }
    }

    /**
     * addMany attaches an array of models to the parent instance with deferred binding support
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
     * remove a model from this relationship type.
     */
    public function remove(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            /**
             * @event model.relation.beforeRemove
             * Called before removing a relation to the model (for AttachOneOrMany, HasOneOrMany & MorphOneOrMany relations)
             *
             * Example usage:
             *
             *     $model->bindEvent('model.relation.beforeRemove', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
             *         if ($relationName === 'perm_relation') {
             *             return false;
             *         }
             *     });
             *
             */
            if ($this->parent->fireEvent('model.relation.beforeRemove', [$this->relationName, $model], true) === false) {
                return;
            }

            if (!$this->isModelRemovable($model)) {
                return;
            }

            $options = $this->parent->getRelationDefinition($this->relationName);

            // Delete or orphan the model
            if (Arr::get($options, 'delete', false)) {
                $model->delete();
            }
            else {
                $model->setAttribute($this->getForeignKeyName(), null);
                $model->save();
            }

            // Use this opportunity to set the relation in memory
            if ($this instanceof HasOne) {
                $this->parent->setRelation($this->relationName, null);
            }
            else {
                $this->parent->unsetRelation($this->relationName);
            }

            /**
             * @event model.relation.remove
             * Called after removing a relation to the model (for AttachOneOrMany, HasOneOrMany & MorphOneOrMany relations)
             *
             * Example usage:
             *
             *     $model->bindEvent('model.relation.remove', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
             *         $relatedClass = get_class($relatedModel);
             *         $modelClass = get_class($model);
             *         traceLog("{$relatedClass} was removed from {$modelClass}.");
             *     });
             *
             */
            $this->parent->fireEvent('model.relation.remove', [$this->relationName, $model]);
        }
        else {
            $this->parent->unbindDeferred($this->relationName, $model, $sessionKey);
        }
    }

    /**
     * isModelRemovable returns true if an existing model is already associated
     */
    protected function isModelRemovable($model): bool
    {
        return ((string) $model->getAttribute($this->getForeignKeyName()) === (string) $this->getParentKey());
    }

    /**
     * ensureRelationIsEmpty ensures the relation is empty, either deleted or nulled.
     */
    protected function ensureRelationIsEmpty()
    {
        $options = $this->parent->getRelationDefinition($this->relationName);

        if (Arr::get($options, 'delete', false)) {
            $this->delete();
        }
        else {
            $this->update([$this->getForeignKeyName() => null]);
        }
    }

    /**
     * getRelatedKeyName
     * @return string
     */
    public function getRelatedKeyName()
    {
        return $this->related->getKeyName();
    }

    /**
     * @deprecated use getForeignKeyName
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * @deprecated use getLocalKeyName
     */
    public function getOtherKey()
    {
        return $this->localKey;
    }
}
