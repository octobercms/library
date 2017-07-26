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
        if ($sessionKey === null) {
            $this->associate($model);
        }
        else {
            $this->child->bindDeferred($this->relationName, $model, $sessionKey);
        }
    }

    /**
     * Removes a model from this relationship type.
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
                $value->bindEventOnce('model.afterSave', function() use ($value){
                    $this->associate($value);
                });
            }

            $this->associate($value);
            $this->child->setRelation($this->relationName, $value);
        }
        else {
            $this->child->setAttribute($this->getForeignKey(), $value);
            $this->child->reloadRelations($this->relationName);
        }
    }

    /**
     * Helper for getting this relationship simple value,
     * generally useful with form values.
     */
    public function getSimpleValue()
    {
        return $this->child->getAttribute($this->getForeignKey());
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
