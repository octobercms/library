<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BelongsToBase;

/**
 * BelongsTo
 */
class BelongsTo extends BelongsToBase
{
    use DeferOneOrMany;
    use DefinedConstraints;

    /**
     * @var string relationName is the "name" of the relationship
     */
    protected $relationName;

    public function __construct(Builder $query, Model $child, $foreignKey, $ownerKey, $relationName)
    {
        $this->relationName = $relationName;

        parent::__construct($query, $child, $foreignKey, $ownerKey, $relationName);

        $this->addDefinedConstraints();
    }

    /**
     * add a model to this relationship type.
     */
    public function add(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            $this->associate($model);
        }
        else {
            $this->child->bindDeferred($this->relationName, $model, $sessionKey);
        }
    }

    /**
     * remove a model from this relationship type.
     */
    public function remove(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            $this->dissociate();
        }
        else {
            $this->child->unbindDeferred($this->relationName, $model, $sessionKey);
        }
    }

    /**
     * associate the model instance to the given parent.
     */
    public function associate($model)
    {
        /**
         * @event model.relation.beforeAssociate
         * Called before associating a relation to the model (only for BelongsTo/MorphTo relations)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.beforeAssociate', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
         *         if ($relationName === 'some_relation') {
         *             return false;
         *         }
         *     });
         *
         */
        if ($this->parent->fireEvent('model.relation.beforeAssociate', [$this->relationName, $model], true) === false) {
            return;
        }

        $result = parent::associate($model);

        /**
         * @event model.relation.associate
         * Called after associating a relation to the model (only for BelongsTo/MorphTo relations)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.associate', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
         *         $relatedClass = get_class($relatedModel);
         *         $modelClass = get_class($model);
         *         traceLog("{$relatedClass} was associated as {$relationName} to {$modelClass}.");
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.associate', [$this->relationName, $model]);

        return $result;
    }

    /**
     * dissociate previously dissociated model from the given parent.
     */
    public function dissociate()
    {
        /**
         * @event model.relation.beforeDissociate
         * Called before dissociating a relation to the model (only for BelongsTo/MorphTo relations)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.beforeDissociate', function (string $relationName) use (\October\Rain\Database\Model $model) {
         *         if ($relationName === 'perm_relation') {
         *             return false;
         *         }
         *     });
         *
         */
        if ($this->parent->fireEvent('model.relation.beforeDissociate', [$this->relationName], true) === false) {
            return;
        }

        $result = parent::dissociate();

        /**
         * @event model.relation.dissociate
         * Called after dissociating a relation to the model (only for BelongsTo/MorphTo relations)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.dissociate', function (string $relationName) use (\October\Rain\Database\Model $model) {
         *         $modelClass = get_class($model);
         *         traceLog("{$relationName} was dissociated from {$modelClass}.");
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.dissociate', [$this->relationName]);

        return $result;
    }

    /**
     * setSimpleValue is a helper for setting this relationship using various expected
     * values. For example, $model->relation = $value;
     */
    public function setSimpleValue($value)
    {
        // Nulling the relationship
        if (!$value) {
            $this->dissociate();
            return;
        }

        if ($value instanceof Model) {
            /*
             * Non existent model, use a single serve event to associate it again when ready
             */
            if (!$value->exists) {
                $value->bindEventOnce('model.afterSave', function () use ($value) {
                    $this->associate($value);
                });
            }

            $this->associate($value);
            $this->child->setRelation($this->relationName, $value);
        }
        else {
            $this->child->setAttribute($this->getForeignKeyName(), $value);
            $this->child->reloadRelations($this->relationName);
        }
    }

    /**
     * getSimpleValue is a helper for getting this relationship simple value,
     * generally useful with form values.
     */
    public function getSimpleValue()
    {
        return $this->child->getAttribute($this->getForeignKeyName());
    }

    /**
     * getOtherKey gets the associated key of the relationship
     * @return string
     */
    public function getOtherKey()
    {
        return $this->ownerKey;
    }
}
