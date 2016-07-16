<?php namespace October\Rain\Database\Relations;

use October\Rain\Database\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as CollectionBase;
use Illuminate\Database\Eloquent\Relations\MorphMany as MorphManyBase;

class MorphMany extends MorphManyBase
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
            $value = new Collection([$value]);
        }

        if ($value instanceof CollectionBase) {
            $collection = $value;

            if ($this->parent->exists) {
                $collection->each(function($instance) {
                    $instance->setAttribute($this->getPlainForeignKey(), $this->getParentKey());
                    $instance->setAttribute($this->getPlainMorphType(), $this->morphClass);
                });
            }
        }
        else {
            $collection = $this->getRelated()->whereIn($this->localKey, (array) $value)->get();
        }

        if ($collection) {
            $this->parent->setRelation($this->relationName, $collection);

            $this->parent->bindEventOnce('model.afterSave', function() use ($collection) {
                $existingIds = $collection->lists($this->localKey);
                $this->whereNotIn($this->localKey, $existingIds)->update([
                    $this->getPlainForeignKey() => null,
                    $this->getPlainMorphType() => null
                ]);
                $collection->each(function($instance) {
                    $instance->setAttribute($this->getPlainForeignKey(), $this->getParentKey());
                    $instance->setAttribute($this->getPlainMorphType(), $this->morphClass);
                    $instance->save(['timestamps' => false]);
                });
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

        if ($relation = $this->parent->$relationName) {
            $value = $relation->lists($this->localKey);
        }

        return $value;
    }
}
