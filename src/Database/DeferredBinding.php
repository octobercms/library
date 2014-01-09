<?php namespace October\Rain\Database;

use Db;

/**
 * Deferred Binding Model
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class DeferredBinding extends Model
{
    public $table = 'deferred_bindings';

    /**
     * {@inheritDoc}
     */
    public $throwOnValidation = false;

    /**
     * Prevents duplicates and conflicting binds.
     */
    public function beforeValidate()
    {
        if ($this->exists)
            return;

        /*
         * Skip repeating bindings
         */
        if ($this->bind) {
            $model = $this->findBindingRecord(1);
            if ($model)
                return false;
        }
        /*
         *Remove add-delete pairs
         */
        else {
            $model = $this->findBindingRecord(1);
            if ($model) {
                $model->deleteCancel();
                return false;
            }
        }
    }

    /**
     * Finds a binding record
     */
    protected function findBindingRecord($isBind)
    {
        $model = self::where('master_type', $this->master_type)
            ->where('master_field', $this->master_field)
            ->where('slave_type', $this->slave_type)
            ->where('slave_id', $this->slave_id)
            ->where('session_key', $this->session_key)
            ->where('bind', $isBind);

        return $model->first();
    }

    /**
     * Cancel all deferred bindings to this model.
     */
    public static function cancelDeferredActions($masterType, $sessionKey)
    {
        $records = self::where('master_type=?', $masterType)
            ->where('session_key=?', $sessionKey)
            ->get();

        foreach ($records as $record) {
            $record->deleteCancel();
        }
    }

    /**
     * Delete this binding and cancel is actions
     */
    public function deleteCancel()
    {
        $this->deleteSlaveRecord();
        $this->delete();
    }

    /**
     * Clean up orphan bindings.
     */
    public static function cleanUp($days = 5)
    {
        $records = self::whereRaw('ADDDATE(created_at, INTERVAL :days DAY) < NOW()', ['days' => $days])->get();

        foreach ($records as $record) {
            $record->deleteCancel();
        }
    }

    /**
     * Logic to cancel a bindings action.
     */
    protected function deleteSlaveRecord()
    {
        /*
         * Try to delete unbound hasOne/hasMany records from the details table
         */
        try
        {
            if (!$this->bind)
                return;

            $masterType = $this->master_type;
            $masterObject = new $masterType();

            if (!$masterObject->isDeferrable($this->master_field))
                return;

            $related = $masterObject->makeRelation($this->master_field);
            $relatedObj = $related->find($this->slave_id);
            if (!$relatedObj)
                return;

            $options = $masterObject->getRelationDefinition($this->master_field);

            // @todo Determine if suitable -- This is an added protection layer
            // if (!array_key_exists('delete', $options) || !$options['delete'])
            //     return;

            $foreignKey = array_get($options, 'foreignKey', $masterObject->getForeignKey());

            // Only delete it if the relationship is null.
            if (!$relatedObj->$foreignKey)
                $relatedObj->delete();
        }
        catch (\Exception $ex)
        {
            // Do nothing
        }
    }
}
