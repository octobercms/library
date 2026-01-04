<?php namespace October\Rain\Database\Models;

use Event;
use Carbon\Carbon;
use October\Rain\Database\Model;
use Throwable;

/**
 * DeferredBinding Model
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class DeferredBinding extends Model
{
    use \October\Rain\Database\Traits\Nullable;

    /**
     * @var string table associated with the model
     */
    public $table = 'deferred_bindings';

    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = ['pivot_data'];

    /**
     * @var array nullable attribute names which should be set to null when empty
     */
    protected $nullable = ['pivot_data'];

    /**
     * @var array hasDeferredCache is a cache for the hasDeferredActions check
     */
    protected static $hasDeferredCache = [];

    /**
     * @var int maxCacheSize limits static cache size to prevent memory issues
     * in long-running processes (Laravel 12 performance optimization)
     */
    protected static $maxCacheSize = 100;

    /**
     * beforeCreate prevents duplicates and conflicting binds
     */
    public function beforeCreate()
    {
        $existingRecord = $this->findBindingRecord();
        if (!$existingRecord) {
            return;
        }

        // Remove add-delete pairs
        if ((bool) $this->is_bind !== (bool) $existingRecord->is_bind) {
            $existingRecord->deleteCancel();
            return false;
        }

        // Skip repeating bindings
        return false;
    }

    /**
     * getPivotDataForBind strips attributes beginning with an underscore, allowing
     * meta data to be stored using the column alongside the data.
     */
    public function getPivotDataForBind($model, $relationName): array
    {
        $data = [];

        foreach ((array) $this->pivot_data as $key => $value) {
            if (str_starts_with($key, '_')) {
                continue;
            }

            $data[$key] = $value;
        }

        if (
            $model->isClassInstanceOf(\October\Contracts\Database\SortableRelationInterface::class) &&
            $model->isSortableRelation($relationName)
        ) {
            $sortColumn = $model->getRelationSortOrderColumn($relationName);
            $data[$sortColumn] = $this->sort_order;
        }

        return $data;
    }

    /**
     * findBindingRecord finds a duplicate binding record
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
     * hasDeferredActions allows efficient and informed checks used by validation.
     * Implements LRU-like cache eviction to prevent unbounded memory growth
     * in long-running processes (Laravel 12 performance optimization).
     */
    public static function hasDeferredActions($masterType, $sessionKey, $fieldName = null): bool
    {
        $cacheKey = "{$masterType}.{$sessionKey}";

        if (!array_key_exists($cacheKey, self::$hasDeferredCache)) {
            // Evict oldest entries if cache exceeds max size (LRU-like eviction)
            if (count(self::$hasDeferredCache) >= self::$maxCacheSize) {
                // Remove oldest 20% of entries to reduce eviction frequency
                $removeCount = max(1, (int) (self::$maxCacheSize * 0.2));
                self::$hasDeferredCache = array_slice(
                    self::$hasDeferredCache,
                    $removeCount,
                    null,
                    true
                );
            }

            self::$hasDeferredCache[$cacheKey] = self::where('master_type', $masterType)
                ->where('session_key', $sessionKey)
                ->pluck('master_field')
                ->all()
            ;
        }

        if ($fieldName !== null) {
            return in_array($fieldName, self::$hasDeferredCache[$cacheKey]);
        }

        return (bool) self::$hasDeferredCache[$cacheKey];
    }

    /**
     * setMaxCacheSize allows configuring the maximum cache size.
     */
    public static function setMaxCacheSize(int $size): void
    {
        self::$maxCacheSize = max(1, $size);
    }

    /**
     * clearCache clears the deferred actions cache.
     */
    public static function clearCache(): void
    {
        self::$hasDeferredCache = [];
    }

    /**
     * cancelDeferredActions cancels all deferred bindings to this model.
     * Uses chunking for memory efficiency (Laravel 12 performance optimization).
     */
    public static function cancelDeferredActions($masterType, $sessionKey)
    {
        self::where('master_type', $masterType)
            ->where('session_key', $sessionKey)
            ->chunkById(100, function ($records) {
                foreach ($records as $record) {
                    $record->deleteCancel();
                }
            })
        ;
    }

    /**
     * cleanUp orphan bindings.
     * Uses chunking and lazy collection for memory efficiency
     * when processing large datasets (Laravel 12 performance optimization).
     */
    public static function cleanUp($days = 5)
    {
        $timestamp = Carbon::now()->subDays($days)->toDateTimeString();

        self::where('created_at', '<', $timestamp)
            ->chunkById(100, function ($records) {
                foreach ($records as $record) {
                    $record->deleteCancel();
                }
            })
        ;
    }

    /**
     * deleteCancel deletes this binding and cancel is actions
     */
    public function deleteCancel()
    {
        $this->deleteSlaveRecord();
        $this->delete();
    }

    /**
     * afterDelete
     */
    public function afterDelete()
    {
        self::$hasDeferredCache = [];
    }

    /**
     * deleteSlaveRecord is logic to cancel a binding action
     */
    protected function deleteSlaveRecord()
    {
        if (!$this->is_bind) {
            return;
        }

        // Try to delete unbound hasOne/hasMany records from the details table
        try {
            $masterType = $this->master_type;
            $masterObject = new $masterType;

            /**
             * @event deferredBinding.newMasterInstance
             * Called after the model is initialized when deleting the slave record
             *
             * Example usage:
             *
             *     $model->bindEvent('deferredBinding.newMasterInstance', function ((\Model) $model) {
             *         if ($model instanceof MyModel) {
             *             $model->some_attribute = true;
             *         }
             *     });
             *
             */
            if (
                ($event = $this->fireEvent('deferredBinding.newMasterInstance', [$masterObject], true)) ||
                ($event = Event::fire('deferredBinding.newMasterInstance', [$this, $masterObject], true))
            ) {
                $masterObject = $event;
            }

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

            // Only delete it if the relationship is null
            $foreignKey = array_get($options, 'key', $masterObject->getForeignKey());
            if ($foreignKey && !$relatedObj->$foreignKey) {
                $relatedObj->delete();
            }
        }
        catch (Throwable $ex) {
            // Do nothing
        }
    }
}
