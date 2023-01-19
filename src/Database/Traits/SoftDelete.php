<?php namespace October\Rain\Database\Traits;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as CollectionBase;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * SoftDelete trait for flagging models as deleted instead of actually deleting them.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait SoftDelete
{
    /**
     * @var bool forceDeleting indicates if the model is currently force deleting.
     */
    protected $forceDeleting = false;

    /**
     * bootSoftDelete trait for a model.
     */
    public static function bootSoftDelete()
    {
        static::addGlobalScope(new SoftDeletingScope);

        static::softDeleted(function($model) {
            /**
             * @event model.afterTrash
             * Called after the model is soft deleted (trashed)
             *
             * Example usage:
             *
             *     $model->bindEvent('model.afterTrash', function() use (\October\Rain\Database\Model $model) {
             *         \Log::info("{$model->name} has been trashed!");
             *     });
             *
             */
            $model->fireEvent('model.afterTrash');
            if ($model->methodExists('afterTrash')) {
                $model->afterTrash();
            }
        });

        static::restoring(function($model) {
            /**
             * @event model.beforeRestore
             * Called before the model is restored from a soft delete
             *
             * Example usage:
             *
             *     $model->bindEvent('model.beforeRestore', function() use (\October\Rain\Database\Model $model) {
             *         \Log::info("{$model->name} is going to be restored!");
             *     });
             *
             */
            $model->fireEvent('model.beforeRestore');
            if ($model->methodExists('beforeRestore')) {
                $model->beforeRestore();
            }
        });

        static::restored(function($model) {
            /**
             * @event model.afterRestore
             * Called after the model is restored from a soft delete
             *
             * Example usage:
             *
             *     $model->bindEvent('model.afterRestore', function() use (\October\Rain\Database\Model $model) {
             *         \Log::info("{$model->name} has been brought back to life!");
             *     });
             *
             */
            $model->fireEvent('model.afterRestore');
            if ($model->methodExists('afterRestore')) {
                $model->afterRestore();
            }
        });
    }

    /**
     * isSoftDelete helper method to check if the model is currently
     * being hard or soft deleted, useful in events.
     * @return bool
     */
    public function isSoftDelete()
    {
        return !$this->forceDeleting;
    }

    /**
     * forceDelete on a soft deleted model.
     */
    public function forceDelete()
    {
        $this->forceDeleting = true;

        $this->delete();

        $this->forceDeleting = false;
    }

    /**
     * performDeleteOnModel performs the actual delete query on this model instance.
     */
    protected function performDeleteOnModel()
    {
        if ($this->forceDeleting) {
            $this->performDeleteOnRelations();

            $this->setKeysForSaveQuery($this->newQuery()->withTrashed())->forceDelete();

            $this->exists = false;
        }

        $this->performSoftDeleteOnRelations();

        $this->runSoftDelete();
    }

    /**
     * performSoftDeleteOnRelations locates relations with softDelete flag and
     * cascades the delete event.
     */
    protected function performSoftDeleteOnRelations()
    {
        $definitions = $this->getRelationDefinitions();
        foreach ($definitions as $type => $relations) {
            foreach ($relations as $name => $options) {
                if (!array_get($options, 'softDelete', false)) {
                    continue;
                }

                if (!$relation = $this->{$name}) {
                    continue;
                }

                if ($relation instanceof EloquentModel) {
                    $relation->delete();
                }
                elseif ($relation instanceof CollectionBase) {
                    $relation->each(function ($model) {
                        $model->delete();
                    });
                }
            }
        }
    }

    /**
     * runSoftDelete performs the actual delete query on this model instance.
     */
    protected function runSoftDelete()
    {
        $query = $this->setKeysForSaveQuery($this->newQuery());

        $time = $this->freshTimestamp();

        $columns = [$this->getDeletedAtColumn() => $this->fromDateTime($time)];

        $this->{$this->getDeletedAtColumn()} = $time;

        if ($this->timestamps && ! is_null($this->getUpdatedAtColumn())) {
            $this->{$this->getUpdatedAtColumn()} = $time;

            $columns[$this->getUpdatedAtColumn()] = $this->fromDateTime($time);
        }

        $query->update($columns);

        $this->syncOriginalAttributes(array_keys($columns));

        $this->fireModelEvent('trashed', false);
    }

    /**
     * restore a soft-deleted model instance.
     * @return bool|null
     */
    public function restore()
    {
        // If the restoring event does not return false, we will proceed with this
        // restore operation. Otherwise, we bail out so the developer will stop
        // the restore totally. We will clear the deleted timestamp and save.
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        $this->performRestoreOnRelations();

        $this->{$this->getDeletedAtColumn()} = null;

        // Once we have saved the model, we will fire the "restored" event so this
        // developer will do anything they need to after a restore operation is
        // totally finished. Then we will return the result of the save call.
        $this->exists = true;

        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }

    /**
     * performRestoreOnRelations locates relations with softDelete flag and cascades
     * the restore event.
     */
    protected function performRestoreOnRelations()
    {
        $definitions = $this->getRelationDefinitions();
        foreach ($definitions as $type => $relations) {
            foreach ($relations as $name => $options) {
                if (!array_get($options, 'softDelete', false)) {
                    continue;
                }

                $relation = $this->{$name}()->onlyTrashed()->getResults();
                if (!$relation) {
                    continue;
                }

                if ($relation instanceof EloquentModel) {
                    $relation->restore();
                }
                elseif ($relation instanceof CollectionBase) {
                    $relation->each(function ($model) {
                        $model->restore();
                    });
                }
            }
        }
    }

    /**
     * trashed determines if the model instance has been soft-deleted.
     * @return bool
     */
    public function trashed()
    {
        return !is_null($this->{$this->getDeletedAtColumn()});
    }

    /**
     * withTrashed gets a new query builder that includes soft deletes.
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function withTrashed()
    {
        return with(new static)->newQueryWithoutScope(new SoftDeletingScope);
    }

    /**
     * onlyTrashed gets a new query builder that only includes soft deletes.
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function onlyTrashed()
    {
        $instance = new static;

        $column = $instance->getQualifiedDeletedAtColumn();

        return $instance->newQueryWithoutScope(new SoftDeletingScope)->whereNotNull($column);
    }

    /**
     * softDeleted registers a "trashed" model event callback with the dispatcher.
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function softDeleted($callback)
    {
        static::registerModelEvent('trashed', $callback);
    }

    /**
     * restoring registers a restoring model event with the dispatcher.
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restoring($callback)
    {
        static::registerModelEvent('restoring', $callback);
    }

    /**
     * restored registers a restored model event with the dispatcher.
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restored($callback)
    {
        static::registerModelEvent('restored', $callback);
    }

    /**
     * getDeletedAtColumn gets the name of the "deleted at" column.
     * @return string
     */
    public function getDeletedAtColumn()
    {
        return defined('static::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
    }

    /**
     * getQualifiedDeletedAtColumn gets the fully qualified "deleted at" column.
     * @return string
     */
    public function getQualifiedDeletedAtColumn()
    {
        return $this->qualifyColumn($this->getDeletedAtColumn());
    }
}
