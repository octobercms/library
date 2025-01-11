<?php namespace October\Rain\Database\Concerns;

use App;

/**
 * HasReplication for a model
 *
 * @see October\Rain\Database\Replicator
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
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
        return App::makeWith('db.replicator', ['model' => $this])->replicate($except);
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
        return App::makeWith('db.replicator', ['model' => $this])->duplicate($except);
    }

    /**
     * newReplicationInstance returns a new instance used by the replicator
     */
    public function newReplicationInstance($attributes)
    {
        $instance = $this->newInstance();

        $instance->setRawAttributes($attributes);

        $instance->fireModelEvent('replicating', false);

        return $instance;
    }

    /**
     * isRelationReplicable determines whether the specified relation should be replicated
     */
    public function isRelationReplicable(string $name): bool
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
