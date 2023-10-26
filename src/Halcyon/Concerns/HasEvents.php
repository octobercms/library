<?php namespace October\Rain\Halcyon\Concerns;

use Illuminate\Contracts\Events\Dispatcher;

/**
 * HasEvents concern for a model
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasEvents
{
    /**
     * @var array eventsBooted is the array of models booted events.
     */
    protected static $eventsBooted = [];

    /**
     * @var \Illuminate\Contracts\Events\Dispatcher dispatcher instance
     */
    protected static $dispatcher;

    /**
     * @var array observables are user exposed observable events.
     */
    protected $observables = [];

    /**
     * bootNicerEvents binds some nicer events to this model, in the format of method overrides.
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
        ];

        foreach ($nicerEvents as $eventMethod => $method) {
            self::registerModelEvent($eventMethod, function ($model) use ($method) {
                $model->fireEvent("model.{$method}");
                return $model->$method();
            });
        }

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
     * flushEventListeners removes all of the event listeners for the model.
     */
    public static function flushEventListeners()
    {
        if (!isset(static::$dispatcher)) {
            return;
        }

        $instance = new static;

        foreach ($instance->getObservableEvents() as $event) {
            static::$dispatcher->forget("halcyon.{$event}: ".static::class);
        }

        static::$eventsBooted = [];
    }

    /**
     * getObservableEvents names.
     * @return array
     */
    public function getObservableEvents()
    {
        return array_merge(
            [
                'creating', 'created', 'updating', 'updated',
                'deleting', 'deleted', 'saving', 'saved',
                'fetching', 'fetched'
            ],
            $this->observables
        );
    }


    /**
     * setObservableEvents names.
     * @param  array  $observables
     * @return $this
     */
    public function setObservableEvents(array $observables)
    {
        $this->observables = $observables;

        return $this;
    }

    /**
     * addObservableEvents name.
     * @param  array|mixed  $observables
     * @return void
     */
    public function addObservableEvents($observables)
    {
        $observables = is_array($observables) ? $observables : func_get_args();

        $this->observables = array_unique(array_merge($this->observables, $observables));
    }

    /**
     * removeObservableEvents name.
     * @param  array|mixed  $observables
     * @return void
     */
    public function removeObservableEvents($observables)
    {
        $observables = is_array($observables) ? $observables : func_get_args();

        $this->observables = array_diff($this->observables, $observables);
    }

    /**
     * getEventDispatcher instance.
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public static function getEventDispatcher()
    {
        return static::$dispatcher;
    }

    /**
     * setEventDispatcher instance.
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @return void
     */
    public static function setEventDispatcher(Dispatcher $dispatcher)
    {
        static::$dispatcher = $dispatcher;
    }

    /**
     * unsetEventDispatcher for models.
     * @return void
     */
    public static function unsetEventDispatcher()
    {
        static::$dispatcher = null;
    }

    /**
     * registerModelEvent with the dispatcher.
     * @param  string  $event
     * @param  \Closure|string  $callback
     * @param  int  $priority
     * @return void
     */
    protected static function registerModelEvent($event, $callback, $priority = 0)
    {
        if (isset(static::$dispatcher)) {
            $name = static::class;

            static::$dispatcher->listen("halcyon.{$event}: {$name}", $callback, $priority);
        }
    }

    /**
     * fireModelEvent for the model.
     * @param  string  $event
     * @param  bool  $halt
     * @return mixed
     */
    protected function fireModelEvent($event, $halt = true)
    {
        if (!isset(static::$dispatcher)) {
            return true;
        }

        // We will append the names of the class to the event to distinguish it from
        // other model events that are fired, allowing us to listen on each model
        // event set individually instead of catching event for all the models.
        $event = "halcyon.{$event}: ".static::class;

        $method = $halt ? 'until' : 'dispatch';

        return static::$dispatcher->$method($event, $this);
    }

    /**
     * Create a new native event for handling beforeFetch().
     * @param Closure|string $callback
     * @return void
     */
    public static function fetching($callback)
    {
        static::registerModelEvent('fetching', $callback);
    }

    /**
     * Create a new native event for handling afterFetch().
     * @param Closure|string $callback
     * @return void
     */
    public static function fetched($callback)
    {
        static::registerModelEvent('fetched', $callback);
    }

    /**
     * Register a saving model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @param  int  $priority
     * @return void
     */
    public static function saving($callback, $priority = 0)
    {
        static::registerModelEvent('saving', $callback, $priority);
    }

    /**
     * Register a saved model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @param  int  $priority
     * @return void
     */
    public static function saved($callback, $priority = 0)
    {
        static::registerModelEvent('saved', $callback, $priority);
    }

    /**
     * Register an updating model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @param  int  $priority
     * @return void
     */
    public static function updating($callback, $priority = 0)
    {
        static::registerModelEvent('updating', $callback, $priority);
    }

    /**
     * Register an updated model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @param  int  $priority
     * @return void
     */
    public static function updated($callback, $priority = 0)
    {
        static::registerModelEvent('updated', $callback, $priority);
    }

    /**
     * Register a creating model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @param  int  $priority
     * @return void
     */
    public static function creating($callback, $priority = 0)
    {
        static::registerModelEvent('creating', $callback, $priority);
    }

    /**
     * Register a created model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @param  int  $priority
     * @return void
     */
    public static function created($callback, $priority = 0)
    {
        static::registerModelEvent('created', $callback, $priority);
    }

    /**
     * Register a deleting model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @param  int  $priority
     * @return void
     */
    public static function deleting($callback, $priority = 0)
    {
        static::registerModelEvent('deleting', $callback, $priority);
    }

    /**
     * Register a deleted model event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @param  int  $priority
     * @return void
     */
    public static function deleted($callback, $priority = 0)
    {
        static::registerModelEvent('deleted', $callback, $priority);
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
         *     $model->bindEvent('model.afterBoot', function () use (\October\Rain\Halcyon\Model $model) {
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
         *     $model->bindEvent('model.afterInit', function () use (\October\Rain\Halcyon\Model $model) {
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
         *     $model->bindEvent('model.beforeCreate', function () use (\October\Rain\Halcyon\Model $model) {
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
         *     $model->bindEvent('model.afterCreate', function () use (\October\Rain\Halcyon\Model $model) {
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
         *     $model->bindEvent('model.beforeUpdate', function () use (\October\Rain\Halcyon\Model $model) {
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
         *     $model->bindEvent('model.afterUpdate', function () use (\October\Rain\Halcyon\Model $model) {
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
         *     $model->bindEvent('model.beforeSave', function () use (\October\Rain\Halcyon\Model $model) {
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
         *     $model->bindEvent('model.afterSave', function () use (\October\Rain\Halcyon\Model $model) {
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
         *     $model->bindEvent('model.beforeDelete', function () use (\October\Rain\Halcyon\Model $model) {
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
         *     $model->bindEvent('model.afterDelete', function () use (\October\Rain\Halcyon\Model $model) {
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
         *     $model->bindEvent('model.beforeFetch', function () use (\October\Rain\Halcyon\Model $model) {
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
         *     $model->bindEvent('model.afterFetch', function () use (\October\Rain\Halcyon\Model $model) {
         *         \Log::info("{$model->name} was retrieved from the database");
         *     });
         *
         */
    }
}
