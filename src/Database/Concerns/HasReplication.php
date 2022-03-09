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
     * replicate the model into a new, non-existing instance.
     *
     * @param  array|null  $except
     * @return static
     */
    public function replicate(array $except = null)
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
                    $instance->replicateRelation($name, $this->$name);
                }
            }
        }

        return $instance;
    }

    /**
     * replicateRelation on this model with the supplied ones
     */
    public function replicateRelation($name, $models)
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

        $relationObject = $this->$name();

        foreach (array_filter($models) as $model) {
            if ($relationObject instanceof HasOneOrMany) {
                $relationObject->add($model->replicate());
            }
            else {
                $relationObject->add($model);
            }
        }
    }

    /**
     * isRelationReplicable determines whether the specified relation should be replicated
     * when replicate() is called instead of save() on the model. Default: false.
     */
    public function isRelationReplicable(string $name): bool
    {
        $definition = $this->getRelationDefinition($name);

        if (!array_key_exists('replicate', $definition)) {
            return false;
        }

        return (bool) $definition['replicate'];
    }
}
