<?php namespace October\Rain\Database\Traits;

use October\Rain\Database\Models\DeferredBinding as DeferredBindingModel;

trait DeferredBinding
{

    /**
     * @var string A unique session key used for deferred binding.
     */
    public $sessionKey;

    /**
     * Bind a deferred relationship to the supplied record.
     */
    public function bindDeferred($relation, $record, $sessionKey)
    {
        $binding = DeferredBindingModel::make();
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
        $binding = DeferredBindingModel::make();
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
        if (!strlen($sessionKey))
            return;

        $bindings = DeferredBindingModel::where('master_type', get_class($this))
            ->where('session_key', $sessionKey)
            ->get();

        foreach ($bindings as $binding) {

            if (!($relationName = $binding->master_field))
                continue;

            if (!$this->isDeferrable($relationName))
                continue;

            /*
             * Find the slave model
             */
            $slaveClass = $binding->slave_type;
            $slaveModel = new $slaveClass();
            $slaveModel = $slaveModel->find($binding->slave_id);
            if (!$slaveModel)
                continue;

            /*
             * Bind/Unbind the relationship, save the related model with any
             * deferred bindings it might have and delete the binding action
             */
            $relationObj = $this->$relationName();

            if ($binding->is_bind)
                $relationObj->add($slaveModel);
            else
                $relationObj->remove($slaveModel);

            $slaveModel->save(null, $sessionKey);

            $binding->delete();
        }
    }

    /**
     * Returns true if a relation exists and can be deferred.
     */
    public function isDeferrable($relationName)
    {
        if (!$this->hasRelation($relationName))
            return false;

        $type = $this->getRelationType($relationName);
        return (
            $type == 'hasMany' ||
            $type == 'hasOne' ||
            $type == 'morphMany' ||
            $type == 'morphToMany' ||
            $type == 'morphedByMany' ||
            $type == 'morphOne' ||
            $type == 'attachMany' ||
            $type == 'attachOne' ||
            $type == 'belongsToMany'
        );
    }

}