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
        return $this->replicateRelationsInternal($except);
    }

    /**
     * duplicateWithRelations replicates a model with special multisite duplication logic.
     * To avoid duplication of has many relations, the logic only propagates relations on
     * the parent model since they are shared via site_root_id beyond this point.
     *
     * @param  array|null  $except
     * @return static
     */
    public function duplicateWithRelations(array $except = null)
    {
        return $this->replicateRelationsInternal($except, ['isDuplicate' => true]);
    }

    /**
     * replicateRelationsInternal
     */
    protected function replicateRelationsInternal(array $except = null, array $options = [])
    {
        extract(array_merge([
            'isDuplicate' => false
        ], $options));

        $defaults = [
            $this->getKeyName(),
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ];

        $isMultisite = $this->isClassInstanceOf(\October\Contracts\Database\MultisiteInterface::class);
        if ($isMultisite) {
            $defaults[] = 'site_root_id';
        }

        $attributes = Arr::except(
            $this->attributes, $except ? array_unique(array_merge($except, $defaults)) : $defaults
        );

        $instance = $this->newInstance();

        $instance->setRawAttributes($attributes);

        $instance->fireModelEvent('replicating', false);

        $definitions = $this->getRelationDefinitions();

        foreach ($definitions as $type => $relations) {
            foreach ($relations as $name => $options) {
                if ($this->isRelationReplicable($name, $isMultisite, $isDuplicate)) {
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
    protected function isRelationReplicable(string $name, bool $isMultisite, bool $isDuplicate): bool
    {
        $relationType = $this->getRelationType($name);
        if ($relationType === 'morphTo') {
            return false;
        }

        // Relation is shared via propagation
        if (!$isDuplicate && $isMultisite && $this->isAttributePropagatable($name)) {
            return false;
        }

        $definition = $this->getRelationDefinition($name);
        if (!array_key_exists('replicate', $definition)) {
            return true;
        }

        return (bool) $definition['replicate'];
    }
}
