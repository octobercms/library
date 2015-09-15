<?php namespace October\Rain\Database\Models;

use Db;
use October\Rain\Database\Model;
use Exception;

/**
 * Deferred Binding Model
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class DeferredBinding extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'deferred_bindings';

    /**
     * Prevents duplicates and conflicting binds.
     */
    public function beforeCreate()
    {
        if ($existingRecord = $this->findBindingRecord()) {
            /*
             * Remove add-delete pairs
             */
            if ($this->is_bind != $existingRecord->is_bind) {
                $existingRecord->deleteCancel();
                return false;
            }
            /*
             * Skip repeating bindings
             */
            else {
                return false;
            }
        }
    }

    /**
     * Finds a duplicate binding record.
     */
    protected function findBindingRecord()
    {
        return self::where('master_type', $this->master_type)
            ->where('master_field', $this->master_field)
            ->where('slave_type', $this->slave_type)
            ->where('slave_id', $this->slave_id)
            ->where('session_key', $this->session_key)
            ->first()
        ;
    }

    /**
     * Cancel all deferred bindings to this model.
     */
    public static function cancelDeferredActions($masterType, $sessionKey)
    {
        $records = self::where('master_type', $masterType)
            ->where('session_key', $sessionKey)
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
            if (!$this->is_bind) {
                return;
            }

            $masterType = $this->master_type;
            $masterObject = new $masterType();

            if (!$masterObject->isDeferrable($this->master_field)) {
                return;
            }

            $related = $masterObject->makeRelation($this->master_field);
            $relatedObj = $related->find($this->slave_id);
            if (!$relatedObj) {
                return;
            }

            $options = $masterObject->getRelationDefinition($this->master_field);

            if (!array_get($options, 'delete', false)) {
                return;
            }

            $foreignKey = array_get($options, 'key', $masterObject->getForeignKey());

            // Only delete it if the relationship is null.
            if (!$relatedObj->$foreignKey) {
                $relatedObj->delete();
            }

        }
        catch (Exception $ex) {
            // Do nothing
        }
    }
}
