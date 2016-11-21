<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphOne as MorphOneBase;

class MorphOne extends MorphOneBase
{
    use MorphOneOrMany;
    use DefinedConstraints;

    /**
     * Create a new has many relationship instance.
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $type, $id, $localKey, $relationName = null)
    {
        $this->relationName = $relationName;

        parent::__construct($query, $parent, $type, $id, $localKey);

        $this->addDefinedConstraints();
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
                    $this->update([
                        $this->getPlainForeignKey() => null,
                        $this->getPlainMorphType() => null
                    ]);
                });
            }
            return;
        }

        if ($value instanceof Model) {
            $instance = $value;

            if ($this->parent->exists) {
                $instance->setAttribute($this->getPlainForeignKey(), $this->getParentKey());
                $instance->setAttribute($this->getPlainMorphType(), $this->morphClass);
            }
        }
        else {
            $instance = $this->getRelated()->find($value);
        }

        if ($instance) {
            $this->parent->setRelation($this->relationName, $instance);

            // Relation is already set, do nothing. This prevents the relationship
            // from being nulled below and left unset because the save will ignore
            // attribute values that are numerically equivalent (not dirty).
            if (
                $instance->getOriginal($this->getPlainForeignKey()) === $this->getParentKey() &&
                $instance->getOriginal($this->getPlainMorphType()) === $this->morphClass
            ) {
                return;
            }

            $this->parent->bindEventOnce('model.afterSave', function() use ($instance){
                $this->update([
                    $this->getPlainForeignKey() => null,
                    $this->getPlainMorphType() => null
                ]);
                $instance->setAttribute($this->getPlainForeignKey(), $this->getParentKey());
                $instance->setAttribute($this->getPlainMorphType(), $this->morphClass);
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

        if ($this->parent->$relationName) {
            $key = $this->getPlainForeignKey();
            $value = $this->parent->$relationName->$key;
        }

        return $value;
    }
}
