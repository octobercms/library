<?php namespace October\Rain\Database\Concerns;

/**
 * HasEvents concern for a model
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasEvents
{
    /**
     * @var array eventsBooted is an array of models booted events
     */
    protected static $eventsBooted = [];

    /**
     * bootNicerEvents to this model, in the format of method overrides.
     */
    protected function bootNicerEvents()
    {
        if (isset(static::$eventsBooted[static::class])) {
            return;
        }

        $nicerEvents = [
            'creating' => 'beforeCreate',
            'created' => 'afterCreate',
            'saving' => 'beforeSave',
            'saved' => 'afterSave',
            'updating' => 'beforeUpdate',
            'updated' => 'afterUpdate',
            'deleting' => 'beforeDelete',
            'deleted' => 'afterDelete',
            'fetching' => 'beforeFetch',
            'fetched' => 'afterFetch',
            'replicating' => 'beforeReplicate',
        ];

        foreach ($nicerEvents as $eventMethod => $method) {
            self::registerModelEvent($eventMethod, function ($model) use ($method) {
                $model->fireEvent("model.{$method}");
                return $model->$method();
            });
        }

        // Hooks for late stage attribute changes
        self::registerModelEvent('creating', function ($model) {
            $model->fireEvent('model.beforeSaveDone');
        });

        self::registerModelEvent('updating', function ($model) {
            $model->fireEvent('model.beforeSaveDone');
        });

        // Boot event
        $this->fireEvent('model.afterBoot');
        $this->afterBoot();

        static::$eventsBooted[static::class] = true;
    }

    /**
     * initializeModelEvent is called every time the model is constructed.
     */
    protected function initializeModelEvent()
    {
        $this->fireEvent('model.afterInit');
        $this->afterInit();
    }

    /**
     * flushEventListeners removes all of the event listeners for the model
     * Also flush registry of models that had events booted
     * Allows painless unit testing.
     * @return void
     */
    public static function flushEventListeners()
    {
        parent::flushEventListeners();
        static::$eventsBooted = [];
    }

    /**
     * getObservableEvents as their names.
     * @return array
     */
    public function getObservableEvents()
    {
        return array_merge(
            [
                'creating', 'created', 'updating', 'updated',
                'deleting', 'deleted', 'saving', 'saved', 'replicating',
                'trashed', 'restoring', 'restored', 'fetching', 'fetched'
            ],
            $this->observables
        );
    }

    /**
     * fetching creates a new native event for handling beforeFetch().
     * @param \Closure|string $callback
     * @return void
     */
    public static function fetching($callback)
    {
        static::registerModelEvent('fetching', $callback);
    }

    /**
     * fetched creates a new native event for handling afterFetch().
     * @param \Closure|string $callback
     * @return void
     */
    public static function fetched($callback)
    {
        static::registerModelEvent('fetched', $callback);
    }

    /**
     * afterBoot is called after the model is constructed for the first time.
     */
    protected function afterBoot()
    {
        /**
         * @event model.afterBoot
         * Called after the model is booted
         *
         * Example usage:
         *
         *     $model->bindEvent('model.afterBoot', function () use (\October\Rain\Database\Model $model) {
         *         \Log::info(get_class($model) . ' has booted');
         *     });
         *
         */
    }

    /**
     * afterInit is called after the model is constructed, a nicer version
     * of overriding the __construct method.
     */
    protected function afterInit()
    {
        /**
         * @event model.afterInit
         * Called after the model is initialized
         *
         * Example usage:
         *
         *     $model->bindEvent('model.afterInit', function () use (\October\Rain\Database\Model $model) {
         *         \Log::info(get_class($model) . ' has initialized');
         *     });
         *
         */
    }

    /**
     * beforeCreate handles the "creating" model event
     */
    protected function beforeCreate()
    {
        /**
         * @event model.beforeCreate
         * Called before the model is created
         *
         * Example usage:
         *
         *     $model->bindEvent('model.beforeCreate', function () use (\October\Rain\Database\Model $model) {
         *         if (!$model->isValid()) {
         *             throw new \Exception("Invalid Model!");
         *         }
         *     });
         *
         */
    }

    /**
     * afterCreate handles the "created" model event
     */
    protected function afterCreate()
    {
        /**
         * @event model.afterCreate
         * Called after the model is created
         *
         * Example usage:
         *
         *     $model->bindEvent('model.afterCreate', function () use (\October\Rain\Database\Model $model) {
         *         \Log::info("{$model->name} was created!");
         *     });
         *
         */
    }

    /**
     * beforeUpdate handles the "updating" model event
     */
    protected function beforeUpdate()
    {
        /**
         * @event model.beforeUpdate
         * Called before the model is updated
         *
         * Example usage:
         *
         *     $model->bindEvent('model.beforeUpdate', function () use (\October\Rain\Database\Model $model) {
         *         if (!$model->isValid()) {
         *             throw new \Exception("Invalid Model!");
         *         }
         *     });
         *
         */
    }

    /**
     * afterUpdate handles the "updated" model event
     */
    protected function afterUpdate()
    {
        /**
         * @event model.afterUpdate
         * Called after the model is updated
         *
         * Example usage:
         *
         *     $model->bindEvent('model.afterUpdate', function () use (\October\Rain\Database\Model $model) {
         *         if ($model->title !== $model->original['title']) {
         *             \Log::info("{$model->name} updated its title!");
         *         }
         *     });
         *
         */
    }

    /**
     * beforeSave handles the "saving" model event
     */
    protected function beforeSave()
    {
        /**
         * @event model.beforeSave
         * Called before the model is created or updated
         *
         * Example usage:
         *
         *     $model->bindEvent('model.beforeSave', function () use (\October\Rain\Database\Model $model) {
         *         if (!$model->isValid()) {
         *             throw new \Exception("Invalid Model!");
         *         }
         *     });
         *
         */
    }

    /**
     * afterSave handles the "saved" model event
     */
    protected function afterSave()
    {
        /**
         * @event model.afterSave
         * Called after the model is created or updated
         *
         * Example usage:
         *
         *     $model->bindEvent('model.afterSave', function () use (\October\Rain\Database\Model $model) {
         *         if ($model->title !== $model->original['title']) {
         *             \Log::info("{$model->name} updated its title!");
         *         }
         *     });
         *
         */
    }

    /**
     * beforeDelete handles the "deleting" model event
     */
    protected function beforeDelete()
    {
        /**
         * @event model.beforeDelete
         * Called before the model is deleted
         *
         * Example usage:
         *
         *     $model->bindEvent('model.beforeDelete', function () use (\October\Rain\Database\Model $model) {
         *         if (!$model->isAllowedToBeDeleted()) {
         *             throw new \Exception("You cannot delete me!");
         *         }
         *     });
         *
         */
    }

    /**
     * afterDelete handles the "deleted" model event
     */
    protected function afterDelete()
    {
        /**
         * @event model.afterDelete
         * Called after the model is deleted
         *
         * Example usage:
         *
         *     $model->bindEvent('model.afterDelete', function () use (\October\Rain\Database\Model $model) {
         *         \Log::info("{$model->name} was deleted");
         *     });
         *
         */
    }

    /**
     * beforeFetch handles the "fetching" model event
     */
    protected function beforeFetch()
    {
        /**
         * @event model.beforeFetch
         * Called before the model is fetched
         *
         * Example usage:
         *
         *     $model->bindEvent('model.beforeFetch', function () use (\October\Rain\Database\Model $model) {
         *         if (!\Auth::getUser()->hasAccess('fetch.this.model')) {
         *             throw new \Exception("You shall not pass!");
         *         }
         *     });
         *
         */
    }

    /**
     * afterFetch handles the "fetched" model event
     */
    protected function afterFetch()
    {
        /**
         * @event model.afterFetch
         * Called after the model is fetched
         *
         * Example usage:
         *
         *     $model->bindEvent('model.afterFetch', function () use (\October\Rain\Database\Model $model) {
         *         \Log::info("{$model->name} was retrieved from the database");
         *     });
         *
         */
    }

    /**
     * beforeReplicate
     */
    protected function beforeReplicate()
    {
        /**
         * @event model.beforeReplicate
         * Called as the model is replicated
         *
         * Example usage:
         *
         *     $model->bindEvent('model.beforeReplicate', function () use (\October\Rain\Database\Model $model) {
         *         \Log::info("{$model->name} is being replicated");
         *     });
         *
         */
    }

    /**
     * beforeRelation is fired on the relation object before it is created
     */
    protected function beforeRelation($name, $relation)
    {
        /**
         * @event model.beforeRelation
         * Called when a new instance of a related model is created
         *
         * Example usage:
         *
         *     $model->bindEvent('model.beforeRelation', function (string $relationName, \Illuminate\Database\Eloquent\Relations\Relation $relatedObject) use (\October\Rain\Database\Model $model) {
         *         // Implement scope
         *         $relatedObject->withLocked();
         *     });
         *
         */
    }

    /**
     * beforeRelation is fired on the relation model instance after it is created
     */
    protected function afterRelation($name, $model)
    {
        /**
         * @event model.afterRelation
         * Called when a new instance of a related model is created
         *
         * Example usage:
         *
         *     $model->bindEvent('model.afterRelation', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
         *         // Transfer custom properties
         *         $relatedModel->isLocked = $model->isLocked;
         *     });
         *
         */
    }
}
