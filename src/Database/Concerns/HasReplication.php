<?php namespace October\Rain\Database\Concerns;

use October\Rain\Support\Arr;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as CollectionBase;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

/**
 * HasReplication for a model
 */
trait HasReplication
{
    /**
     * replicateWithRelations replicates the model into a new, non-existing instance,
     * including replicating relations.
     *
     * @param  array|null  $except
     * @return static
     */
    public function replicateWithRelations(array $except = null)
    {
        $defaults = [
            $this->getKeyName(),
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ];

        $attributes = Arr::except(
            $this->attributes, $except ? array_unique(array_merge($except, $defaults)) : $defaults
        );

        $instance = $this->newInstance();

        $instance->setRawAttributes($attributes);

        $instance->fireModelEvent('replicating', false);

        $definitions = $this->getRelationDefinitions();

        foreach ($definitions as $type => $relations) {
            foreach ($relations as $name => $options) {
                if ($this->isRelationReplicable($name)) {
                    $this->replicateRelationInternal($instance->$name(), $this->$name);
                }
            }
        }

        return $instance;
    }

    /**
     * replicateRelationInternal on the model instance with the supplied ones
     */
    protected function replicateRelationInternal($relationObject, $models)
    {
        if ($models instanceof CollectionBase) {
            $models = $models->all();
        }
        elseif ($models instanceof EloquentModel) {
            $models = [$models];
        }
        else {
            $models = (array) $models;
        }

        foreach (array_filter($models) as $model) {
            if ($relationObject instanceof HasOneOrMany) {
                $relationObject->add($model->replicateWithRelations());
            }
            else {
                $relationObject->add($model);
            }
        }
    }

    /**
     * isRelationReplicable determines whether the specified relation should be replicated
     * when replicateWithRelations() is called instead of save() on the model. Default: true.
     */
    protected function isRelationReplicable(string $name): bool
    {
        $relationType = $this->getRelationType($name);
        if ($relationType === 'morphTo') {
            return false;
        }

        $definition = $this->getRelationDefinition($name);
        if (!array_key_exists('replicate', $definition)) {
            return true;
        }

        return (bool) $definition['replicate'];
    }
}
