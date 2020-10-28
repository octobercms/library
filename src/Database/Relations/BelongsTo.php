<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BelongsToBase;

class BelongsTo extends BelongsToBase
{
    use DeferOneOrMany;
    use DefinedConstraints;

    /**
     * @var string The "name" of the relationship.
     */
    protected $relationName;

    public function __construct(Builder $query, Model $child, $foreignKey, $ownerKey, $relationName)
    {
        $this->relationName = $relationName;

        parent::__construct($query, $child, $foreignKey, $ownerKey, $relationName);

        $this->addDefinedConstraints();
    }

    /**
     * Adds a model to this relationship type.
     */
    public function add(Model $model, $sessionKey = null)
    {
        /**
         * @event model.relation.beforeAssociate
         * Called before associating a relation to the model (only for BelongsTo/MorphTo relations)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.beforeAssociate', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
         *         TODO: add example code
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.beforeAssociate', [$this->relationName, $this->related]);

        if ($sessionKey === null) {
            $this->associate($model);
        }
        else {
            $this->child->bindDeferred($this->relationName, $model, $sessionKey);
        }

        /**
         * @event model.relation.afterAssociate
         * Called after associating a relation to the model (only for BelongsTo/MorphTo relations)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.afterAssociate', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
         *         TODO: add example code
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.afterAssociate', [$this->relationName, $this->related]);
    }

    /**
     * Removes a model from this relationship type.
     */
    public function remove(Model $model, $sessionKey = null)
    {
        /**
         * @event model.relation.beforeDissociate
         * Called before dissociating a relation to the model (only for BelongsTo/MorphTo relations)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.beforeDissociate', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
         *         TODO: add example code
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.beforeDissociate', [$this->relationName, $this->related], true);

        if ($sessionKey === null) {
            $this->dissociate();
        }
        else {
            $this->child->unbindDeferred($this->relationName, $model, $sessionKey);
        }

        /**
         * @event model.relation.afterDissociate
         * Called after dissociating a relation to the model (only for BelongsTo/MorphTo relations)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.afterDissociate', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
         *         TODO: add example code
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.afterDissociate', [$this->relationName, $this->related]);
    }

    /**
     * Helper for setting this relationship using various expected
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
     * Helper for getting this relationship simple value,
     * generally useful with form values.
     */
    public function getSimpleValue()
    {
        return $this->child->getAttribute($this->getForeignKeyName());
    }

    /**
     * Get the associated key of the relationship.
     * @return string
     */
    public function getOtherKey()
    {
        return $this->ownerKey;
    }
}
