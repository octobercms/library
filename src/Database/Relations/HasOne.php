<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne as HasOneBase;

class HasOne extends HasOneBase
{
    use HasOneOrMany;
    use DefinedConstraints;

    /**
     * Create a new has many relationship instance.
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey, $relationName = null)
    {
        $this->relationName = $relationName;

        parent::__construct($query, $parent, $foreignKey, $localKey);

        $this->addDefinedConstraints();
    }

    /**
     * Get the results of the relationship.
     * @return mixed
     */
    public function getResults()
    {
        // New models have no possibility of having a relationship here
        // so prevent the first orphaned relation from being used.
        if (!$this->parent->exists) {
            return null;
        }

        return parent::getResults();
    }

    /**
     * Helper for setting this relationship using various expected
     * values. For example, $model->relation = $value;
     */
    public function setSimpleValue($value)
    {
        if (is_array($value)) {
            return;
        }

        // Nulling the relationship
        if (!$value) {
            if ($this->parent->exists) {
                $this->parent->bindEventOnce('model.afterSave', function() {
                    $this->update([$this->getForeignKeyName() => null]);
                });
            }
            return;
        }

        if ($value instanceof Model) {
            $instance = $value;

            if ($this->parent->exists) {
                $instance->setAttribute($this->getForeignKeyName(), $this->getParentKey());
            }
        }
        else {
            $instance = $this->getRelated()->find($value);
        }

        if ($instance) {
            $this->parent->setRelation($this->relationName, $instance);

            $this->parent->bindEventOnce('model.afterSave', function() use ($instance) {
                // Relation is already set, do nothing. This prevents the relationship
                // from being nulled below and left unset because the save will ignore
                // attribute values that are numerically equivalent (not dirty).
                if ($instance->getOriginal($this->getForeignKeyName()) == $this->getParentKey()) {
                    return;
                }

                $this->update([$this->getForeignKeyName() => null]);
                $instance->setAttribute($this->getForeignKeyName(), $this->getParentKey());
                $instance->save(['timestamps' => false]);
            });
        }
    }

    /**
     * Helper for getting this relationship simple value,
     * generally useful with form values.
     */
    public function getSimpleValue()
    {
        $value = null;
        $relationName = $this->relationName;

        if ($this->parent->{$relationName}) {
            $key = $this->getForeignKeyName();
            $value = $this->parent->{$relationName}->{$key};
        }

        return $value;
    }
}
