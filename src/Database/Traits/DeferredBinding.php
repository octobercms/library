<?php namespace October\Rain\Database\Traits;

use October\Rain\Database\Models\DeferredBinding as DeferredBindingModel;

/**
 * DeferredBinding trait is implemented by all models
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait DeferredBinding
{
    /**
     * @var string sessionKey is a unique session key used for deferred binding
     */
    public $sessionKey;

    /**
     * isDeferrable returns true if a relation exists and can be deferred
     */
    public function isDeferrable($relationName): bool
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
     * hasDeferred returns true if a deferred record exists for a relation
     */
    public function hasDeferred($sessionKey = null, $relationName = null): bool
    {
        if ($sessionKey === null) {
            $sessionKey = $this->sessionKey;
        }

        return DeferredBindingModel::hasDeferredActions(get_class($this), $sessionKey, $relationName);
    }

    /**
     * bindDeferred binds a deferred relationship to the supplied record
     */
    public function bindDeferred($relation, $record, $sessionKey, $pivotData = []): DeferredBindingModel
    {
        $binding = new DeferredBindingModel;
        $binding->setConnection($this->getConnectionName());
        $binding->master_type = get_class($this);
        $binding->master_field = $relation;
        $binding->slave_type = get_class($record);
        $binding->slave_id = $record->getKey();
        $binding->pivot_data = $pivotData;
        $binding->session_key = $sessionKey;
        $binding->is_bind = true;

        /**
         * @event deferredBinding.newBindInstance
         * Called after the DeferredBindingModel is initialized for binding
         *
         * Example usage:
         *
         *     $model->bindEvent('deferredBinding.newBindInstance', function ((\Model) $model) {
         *         $model->some_attribute = true;
         *     });
         *
         */
        if ($event = $this->fireEvent('deferredBinding.newBindInstance', $binding, true)) {
            $binding = $event;
        }

        $binding->save();

        return $binding;
    }

    /**
     * unbindDeferred unbinds a deferred relationship to the supplied record
     */
    public function unbindDeferred($relation, $record, $sessionKey): DeferredBindingModel
    {
        $binding = new DeferredBindingModel;
        $binding->setConnection($this->getConnectionName());
        $binding->master_type = get_class($this);
        $binding->master_field = $relation;
        $binding->slave_type = get_class($record);
        $binding->slave_id = $record->getKey();
        $binding->session_key = $sessionKey;
        $binding->is_bind = false;

        /**
         * @event deferredBinding.newUnbindInstance
         * Called after the DeferredBindingModel is initialized for unbinding
         *
         * Example usage:
         *
         *     $model->bindEvent('deferredBinding.newUnbindInstance', function ((\Model) $model) {
         *         $model->some_attribute = true;
         *     });
         *
         */
        if ($event = $this->fireEvent('deferredBinding.newUnbindInstance', $binding, true)) {
            $binding = $event;
        }

        $binding->save();

        return $binding;
    }

    /**
     * cancelDeferred cancels all deferred bindings to this model
     */
    public function cancelDeferred($sessionKey): void
    {
        DeferredBindingModel::cancelDeferredActions(get_class($this), $sessionKey);
    }

    /**
     * commitDeferred commits all deferred bindings to this model
     */
    public function commitDeferred($sessionKey)
    {
        $this->commitDeferredOfType($sessionKey);
    }

    /**
     * commitDeferredBefore is used internally to commit all deferred bindings before saving.
     * It is a rare need to have to call this, since it only applies to the
     * "belongs to" relationship which generally does not need deferring.
     */
    protected function commitDeferredBefore($sessionKey)
    {
        $this->commitDeferredOfType($sessionKey, 'belongsTo');
    }

    /**
     * commitDeferredAfter is used internally to commit all deferred bindings after saving
     */
    protected function commitDeferredAfter($sessionKey)
    {
        $this->commitDeferredOfType($sessionKey, null, 'belongsTo');
    }

    /**
     * commitDeferredOfType is an internal method for committing deferred relations
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

            // Find the slave model
            $slaveClass = $binding->slave_type;
            $slaveModel = $this->makeRelation($relationName);
            if (!is_a($slaveModel, $slaveClass)) {
                continue;
            }

            $slaveModel = $slaveModel->find($binding->slave_id);
            if (!$slaveModel) {
                continue;
            }

            // Bind/Unbind the relationship, save the related model with any
            // deferred bindings it might have and delete the binding action
            $relationObj = $this->$relationName();
            if ($binding->is_bind) {
                if (in_array($relationType, ['belongsToMany', 'morphToMany', 'morphedByMany'])) {
                    $pivotData = $binding->getPivotDataForBind($this, $relationName);
                    $relationObj->add($slaveModel, null, $pivotData);
                }
                else {
                    $relationObj->add($slaveModel);
                }
            }
            else {
                $relationObj->remove($slaveModel);
            }

            $binding->delete();
        }
    }

    /**
     * getDeferredBindingRecords returns any outstanding binding records for this model
     * @return \October\Rain\Database\Collection
     */
    protected function getDeferredBindingRecords($sessionKey)
    {
        $binding = new DeferredBindingModel;

        $binding->setConnection($this->getConnectionName());

        return $binding
            ->where('master_type', get_class($this))
            ->where('session_key', $sessionKey)
            ->get()
        ;
    }

    /**
     * getDeferrableRelationTypes returns all possible relation types that can be deferred
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
