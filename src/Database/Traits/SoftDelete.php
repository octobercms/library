<?php namespace October\Rain\Database\Traits;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as CollectionBase;
use Illuminate\Database\Eloquent\SoftDeletingScope;

trait SoftDelete
{

    /**
     * Indicates if the model is currently force deleting.
     *
     * @var bool
     */
    protected $forceDeleting = false;

    /**
     * Boot the soft deleting trait for a model.
     *
     * @return void
     */
    public static function bootSoftDelete()
    {
        static::addGlobalScope(new SoftDeletingScope);

        static::restoring(function($model) {
            $model->fireEvent('model.beforeRestore');
            if ($model->methodExists('beforeRestore')) {
                $model->beforeRestore();
            }
        });

        static::restored(function($model) {
            $model->fireEvent('model.afterRestore');
            if ($model->methodExists('afterRestore')) {
                $model->afterRestore();
            }
        });
    }

    /**
     * Helper method to check if the model is currently
     * being hard or soft deleted, useful in events.
     *
     * @return bool
     */
    public function isSoftDelete()
    {
        return !$this->forceDeleting;
    }

    /**
     * Force a hard delete on a soft deleted model.
     *
     * @return void
     */
    public function forceDelete()
    {
        $this->forceDeleting = true;

        $this->delete();

        $this->forceDeleting = false;
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return mixed
     */
    protected function performDeleteOnModel()
    {
        if ($this->forceDeleting) {
            $this->performDeleteOnRelations();
            return $this->withTrashed()->where($this->getKeyName(), $this->getKey())->forceDelete();
        }

        $this->performSoftDeleteOnRelations();
        return $this->runSoftDelete();
    }

    /**
     * Locates relations with softDelete flag and cascades the delete event.
     *
     * @return void
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
                    $relation->each(function($model) {
                        $model->delete();
                    });
                }
            }
        }
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function runSoftDelete()
    {
        $query = $this->newQuery()->where($this->getKeyName(), $this->getKey());

        $this->{$this->getDeletedAtColumn()} = $time = $this->freshTimestamp();

        $query->update(array($this->getDeletedAtColumn() => $this->fromDateTime($time)));
    }

    /**
     * Restore a soft-deleted model instance.
     *
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
        $result = $this->save();

        $this->fireModelEvent('restored', false);

        return $result;
    }

    /**
     * Locates relations with softDelete flag and cascades the restore event.
     *
     * @return void
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
                    $relation->each(function($model) {
                        $model->restore();
                    });
                }
            }
        }
    }

    /**
     * Determine if the model instance has been soft-deleted.
     *
     * @return bool
     */
    public function trashed()
    {
        return !is_null($this->{$this->getDeletedAtColumn()});
    }

    /**
     * Get a new query builder that includes soft deletes.
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function withTrashed()
    {
        return with(new static)->newQueryWithoutScope(new SoftDeletingScope);
    }

    /**
     * Get a new query builder that only includes soft deletes.
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function onlyTrashed()
    {
        $instance = new static;

        $column = $instance->getQualifiedDeletedAtColumn();

        return $instance->newQueryWithoutScope(new SoftDeletingScope)->whereNotNull($column);
    }

    /**
     * Register a restoring model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restoring($callback)
    {
        static::registerModelEvent('restoring', $callback);
    }

    /**
     * Register a restored model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restored($callback)
    {
        static::registerModelEvent('restored', $callback);
    }

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getDeletedAtColumn()
    {
        return defined('static::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
    }

    /**
     * Get the fully qualified "deleted at" column.
     *
     * @return string
     */
    public function getQualifiedDeletedAtColumn()
    {
        return $this->getTable().'.'.$this->getDeletedAtColumn();
    }

}
