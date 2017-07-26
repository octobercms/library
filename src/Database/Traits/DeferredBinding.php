<?php namespace October\Rain\Database\Traits;

use October\Rain\Database\Models\DeferredBinding as DeferredBindingModel;

trait DeferredBinding
{
    /**
     * @var string A unique session key used for deferred binding.
     */
    public $sessionKey;

    /**
     * @var \October\Rain\Database\Collection Deferred binding lookup cache.
     */
    protected $deferredBindingCache = null;

    /**
     * Returns true if a relation exists and can be deferred.
     */
    public function isDeferrable($relationName)
    {
        if (!$this->hasRelation($relationName)) {
            return false;
        }

        return in_array(
            $this->getRelationType($relationName),
            $this->getDeferrableRelationTypes()
        );
    }

    /**
     * Bind a deferred relationship to the supplied record.
     */
    public function bindDeferred($relation, $record, $sessionKey)
    {
        $binding = new DeferredBindingModel;
        $binding->setConnection($this->getConnectionName());
        $binding->master_type = get_class($this);
        $binding->master_field = $relation;
        $binding->slave_type = get_class($record);
        $binding->slave_id = $record->getKey();
        $binding->session_key = $sessionKey;
        $binding->is_bind = true;
        $binding->save();
        return $binding;
    }

    /**
     * Unbind a deferred relationship to the supplied record.
     */
    public function unbindDeferred($relation, $record, $sessionKey)
    {
        $binding = new DeferredBindingModel;
        $binding->setConnection($this->getConnectionName());
        $binding->master_type = get_class($this);
        $binding->master_field = $relation;
        $binding->slave_type = get_class($record);
        $binding->slave_id = $record->getKey();
        $binding->session_key = $sessionKey;
        $binding->is_bind = false;
        $binding->save();
        return $binding;
    }

    /**
     * Cancel all deferred bindings to this model.
     */
    public function cancelDeferred($sessionKey)
    {
        DeferredBindingModel::cancelDeferredActions(get_class($this), $sessionKey);
    }

    /**
     * Commit all deferred bindings to this model.
     */
    public function commitDeferred($sessionKey)
    {
        $this->commitDeferredOfType($sessionKey);
        $this->deferredBindingCache = null;
    }

    /**
     * Internally used method to commit all deferred bindings before saving.
     * It is a rare need to have to call this, since it only applies to the
     * "belongs to" relationship which generally does not need deferring.
     */
    protected function commitDeferredBefore($sessionKey)
    {
        $this->commitDeferredOfType($sessionKey, 'belongsTo');
    }

    /**
     * Internally used method to commit all deferred bindings after saving.
     */
    protected function commitDeferredAfter($sessionKey)
    {
        $this->commitDeferredOfType($sessionKey, null, 'belongsTo');
        $this->deferredBindingCache = null;
    }

    /**
     * Internal method for commiting deferred relations.
     */
    protected function commitDeferredOfType($sessionKey, $include = null, $exclude = null)
    {
        if (!strlen($sessionKey)) {
            return;
        }

        $bindings = $this->getDeferredBindingRecords($sessionKey);

        foreach ($bindings as $binding) {

            if (!($relationName = $binding->master_field)) {
                continue;
            }

            if (!$this->hasRelation($relationName)) {
                continue;
            }

            $relationType = $this->getRelationType($relationName);
            $allowedTypes = $this->getDeferrableRelationTypes();

            if ($include) {
                $allowedTypes = array_intersect($allowedTypes, (array) $include);
            }
            elseif ($exclude) {
                $allowedTypes = array_diff($allowedTypes, (array) $exclude);
            }

            if (!in_array($relationType, $allowedTypes)) {
                continue;
            }

            /*
             * Find the slave model
             */
            $slaveClass = $binding->slave_type;
            $slaveModel = new $slaveClass;
            $slaveModel = $slaveModel->find($binding->slave_id);

            if (!$slaveModel) {
                continue;
            }

            /*
             * Bind/Unbind the relationship, save the related model with any
             * deferred bindings it might have and delete the binding action
             */
            $relationObj = $this->$relationName();

            if ($binding->is_bind) {
                $relationObj->add($slaveModel);
            }
            else {
                $relationObj->remove($slaveModel);
            }

            $binding->delete();
        }
    }

    /**
     * Returns any outstanding binding records for this model.
     * @return \October\Rain\Database\Collection
     */
    protected function getDeferredBindingRecords($sessionKey, $force = false)
    {
        if ($this->deferredBindingCache !== null && !$force) {
            return $this->deferredBindingCache;
        }

        $binding = new DeferredBindingModel;

        $binding->setConnection($this->getConnectionName());

        return $this->deferredBindingCache = $binding
            ->where('master_type', get_class($this))
            ->where('session_key', $sessionKey)
            ->get()
        ;
    }

    /**
     * Returns all possible relation types that can be deferred.
     * @return array
     */
    protected function getDeferrableRelationTypes()
    {
        return [
            'hasMany',
            'hasOne',
            'morphMany',
            'morphToMany',
            'morphedByMany',
            'morphOne',
            'attachMany',
            'attachOne',
            'belongsToMany',
            'belongsTo'
        ];
    }
}
