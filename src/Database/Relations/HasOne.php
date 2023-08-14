<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne as HasOneBase;

/**
 * HasOne
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class HasOne extends HasOneBase
{
    use HasOneOrMany;
    use DefinedConstraints;

    /**
     * __construct a new has many relationship instance.
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey, $relationName = null)
    {
        $this->relationName = $relationName;

        parent::__construct($query, $parent, $foreignKey, $localKey);

        $this->addDefinedConstraints();
    }

    /**
     * setSimpleValue helper for setting this relationship using various expected
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
                    $this->ensureRelationIsEmpty();
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

        if (!$instance) {
            return;
        }

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

    /**
     * getSimpleValue helper for getting this relationship simple value,
     * generally useful with form values.
     */
    public function getSimpleValue()
    {
        $value = null;
        $relationName = $this->relationName;

        if ($related = $this->parent->{$relationName}) {
            $key = $this->getRelatedKeyName();
            $value = $related->{$key};
        }

        return $value;
    }
}
