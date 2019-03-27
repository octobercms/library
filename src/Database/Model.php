<?php namespace October\Rain\Database;

use Db;
use Input;
use Closure;
use October\Rain\Support\Arr;
use October\Rain\Support\Str;
use October\Rain\Argon\Argon;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as CollectionBase;
use DateTimeInterface;
use Exception;

/**
 * Active Record base class.
 *
 * Extends Eloquent with added extendability and deferred bindings.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class Model extends EloquentModel
{
    use Concerns\HasRelationships;
    use \October\Rain\Support\Traits\Emitter;
    use \October\Rain\Extension\ExtendableTrait;
    use \October\Rain\Database\Traits\DeferredBinding;

    /**
     * @var array Behaviors implemented by this model.
     */
    public $implement;

    /**
     * @var array Make the model's attributes public so behaviors can modify them.
     */
    public $attributes = [];

    /**
     * @var array List of attribute names which are json encoded and decoded from the database.
     */
    protected $jsonable = [];

    /**
     * @var array List of datetime attributes to convert to an instance of Carbon/DateTime objects.
     */
    protected $dates = [];

    /**
     * @var bool Indicates if duplicate queries from this model should be cached in memory.
     */
    public $duplicateCache = true;

    /**
     * @var array The array of models booted events.
     */
    protected static $eventsBooted = [];

    /**
     * Constructor
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct();

        $this->bootNicerEvents();

        $this->extendableConstruct();

        $this->fill($attributes);
    }

    /**
     * Create a new model and return the instance.
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public static function make($attributes = [])
    {
        return new static($attributes);
    }

    /**
     * Save a new model and return the instance.
     * @param array $attributes
     * @param string $sessionKey
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public static function create(array $attributes = [], $sessionKey = null)
    {
        $model = new static($attributes);

        $model->save(null, $sessionKey);

        return $model;
    }

    /**
     * Reloads the model attributes from the database.
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function reload()
    {
        static::flushDuplicateCache();

        if (!$this->exists) {
            $this->syncOriginal();
        }
        elseif ($fresh = static::find($this->getKey())) {
            $this->setRawAttributes($fresh->getAttributes(), true);
        }

        return $this;
    }

    /**
     * Reloads the model relationship cache.
     * @param string  $relationName
     * @return void
     */
    public function reloadRelations($relationName = null)
    {
        static::flushDuplicateCache();

        if (!$relationName) {
            $this->setRelations([]);
        }
        else {
            unset($this->relations[$relationName]);
        }
    }

    /**
     * Extend this object properties upon construction.
     */
    public static function extend(Closure $callback)
    {
        self::extendableExtendCallback($callback);
    }

    /**
     * Bind some nicer events to this model, in the format of method overrides.
     */
    protected function bootNicerEvents()
    {
        $class = get_called_class();

        if (isset(static::$eventsBooted[$class])) {
            return;
        }

        $radicals = ['creat', 'sav', 'updat', 'delet', 'fetch'];
        $hooks = ['before' => 'ing', 'after' => 'ed'];

        foreach ($radicals as $radical) {
            foreach ($hooks as $hook => $event) {
                $eventMethod = $radical . $event; // saving / saved
                $method = $hook . ucfirst($radical); // beforeSave / afterSave

                if ($radical != 'fetch') {
                    $method .= 'e';
                }

                self::$eventMethod(function($model) use ($method) {
                    $model->fireEvent('model.' . $method);

                    if ($model->methodExists($method)) {
                        return $model->$method();
                    }
                });
            }
        }

        /*
         * Hook to boot events
         */
        static::registerModelEvent('booted', function($model){
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
            $model->fireEvent('model.afterBoot');

            if ($model->methodExists('afterBoot')) {
                return $model->afterBoot();
            }
        });

        static::$eventsBooted[$class] = true;
    }

    /**
     * Remove all of the event listeners for the model
     * Also flush registry of models that had events booted
     * Allows painless unit testing.
     *
     * @override
     * @return void
     */
    public static function flushEventListeners()
    {
        parent::flushEventListeners();
        static::$eventsBooted = [];
    }

    /**
     * Handle the "creating" model event
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
     * Handle the "created" model event
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
     * Handle the "updating" model event
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
     * Handle the "updated" model event
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
     * Handle the "saving" model event
     */
    protected function beforeSave()
    {
        /**
         * @event model.beforeSave
         * Called before the model is saved
         * > **Note:** This is called both when creating and updating
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
     * Handle the "saved" model event
     */
    protected function afterSave()
    {
        /**
         * @event model.afterSave
         * Called after the model is saved
         * > **Note:** This is called both when creating and updating
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
     * Handle the "deleting" model event
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
     * Handle the "deleted" model event
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
     * Handle the "fetching" model event
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
     * Handle the "fetched" model event
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
     * Flush the memory cache.
     * @return void
     */
    public static function flushDuplicateCache()
    {
        MemoryCache::instance()->flush();
    }

    /**
     * Create a new model instance that is existing.
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $instance = $this->newInstance([], true);

        if ($instance->fireModelEvent('fetching') === false) {
            return $instance;
        }

        $instance->setRawAttributes((array) $attributes, true);

        $instance->fireModelEvent('fetched', false);

        $instance->setConnection($connection ?: $this->connection);

        return $instance;
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
     * Checks if an attribute is jsonable or not.
     *
     * @return array
     */
    public function isJsonable($key)
    {
        return in_array($key, $this->jsonable);
    }

    /**
     * Get the jsonable attributes name
     *
     * @return array
     */
    public function getJsonable()
    {
        return $this->jsonable;
    }

    /**
     * Set the jsonable attributes for the model.
     *
     * @param  array  $jsonable
     * @return $this
     */
    public function jsonable(array $jsonable)
    {
        $this->jsonable = $jsonable;

        return $this;
    }

    //
    // Overrides
    //

    /**
     * Get the observable event names.
     * @return array
     */
    public function getObservableEvents()
    {
        return array_merge(
            [
                'creating', 'created', 'updating', 'updated',
                'deleting', 'deleted', 'saving', 'saved',
                'restoring', 'restored', 'fetching', 'fetched'
            ],
            $this->observables
        );
    }

    /**
     * Get a fresh timestamp for the model.
     *
     * @return \October\Rain\Argon\Argon
     */
    public function freshTimestamp()
    {
        return new Argon;
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * @param  mixed  $value
     * @return \Carbon\Carbon
     */
    protected function asDateTime($value)
    {
        if ($value instanceof Argon) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return new Argon(
                $value->format('Y-m-d H:i:s.u'), $value->getTimezone()
            );
        }

        if (is_numeric($value)) {
            return Argon::createFromTimestamp($value);
        }

        if ($this->isStandardDateFormat($value)) {
            return Argon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        return Argon::createFromFormat(
            str_replace('.v', '.u', $this->getDateFormat()), $value
        );
    }

    /**
     * Convert a DateTime to a storable string.
     *
     * @param  \DateTime|int  $value
     * @return string
     */
    public function fromDateTime($value)
    {
        if (is_null($value)) {
            return $value;
        }

        return parent::fromDateTime($value);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \October\Rain\Database\QueryBuilder $query
     * @return \October\Rain\Database\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return \October\Rain\Database\QueryBuilder
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();

        $grammar = $conn->getQueryGrammar();

        $builder = new QueryBuilder($conn, $grammar, $conn->getPostProcessor());

        if ($this->duplicateCache) {
            $builder->enableDuplicateCache();
        }

        return $builder;
    }

    /**
     * Create a new Model Collection instance.
     *
     * @param  array  $models
     * @return \October\Rain\Database\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    //
    // Magic
    //

    public function __get($name)
    {
        return $this->extendableGet($name);
    }

    public function __set($name, $value)
    {
        return $this->extendableSet($name, $value);
    }

    public function __call($name, $params)
    {
        /*
         * Never call handleRelation() anywhere else as it could
         * break getRelationCaller(), use $this->{$name}() instead
         */
        if ($this->hasRelation($name)) {
            return $this->handleRelation($name);
        }

        return $this->extendableCall($name, $params);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return !is_null($this->getAttribute($key));
    }

    /**
     * This a custom piece of logic specifically to satisfy Twig's
     * desire to return a relation object instead of loading the
     * related model.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        if ($result = parent::offsetExists($offset)) {
            return $result;
        }

        return $this->hasRelation($offset);
    }

    //
    // Pivot
    //

    /**
     * Create a generic pivot model instance.
     * @param  \October\Rain\Database\Model  $parent
     * @param  array  $attributes
     * @param  string  $table
     * @param  bool  $exists
     * @param  string|null  $using
     * @return \October\Rain\Database\Pivot
     */
    public function newPivot(EloquentModel $parent, array $attributes, $table, $exists, $using = null)
    {
        return $using
            ? $using::fromRawAttributes($parent, $attributes, $table, $exists)
            : new Pivot($parent, $attributes, $table, $exists);
    }

    /**
     * Create a pivot model instance specific to a relation.
     * @param  \October\Rain\Database\Model  $parent
     * @param  string  $relationName
     * @param  array   $attributes
     * @param  string  $table
     * @param  bool    $exists
     * @return \October\Rain\Database\Pivot
     */
    public function newRelationPivot($relationName, $parent, $attributes, $table, $exists)
    {
        $definition = $this->getRelationDefinition($relationName);

        if (!is_null($definition) && array_key_exists('pivotModel', $definition)) {
            $pivotModel = $definition['pivotModel'];
            return new $pivotModel($parent, $attributes, $table, $exists);
        }
    }

    //
    // Saving
    //

    /**
     * Save the model to the database. Is used by {@link save()} and {@link forceSave()}.
     * @param array $options
     * @return bool
     */
    protected function saveInternal($options = [])
    {
        /**
         * @event model.saveInternal
         * Called before the model is saved
         *
         * Example usage:
         *
         *     $model->bindEvent('model.saveInternal', function ((array) $attributes, (array) $options) use (\October\Rain\Database\Model $model) {
         *         // Prevent anything from saving ever!
         *         return false;
         *     });
         *
         */
        if ($this->fireEvent('model.saveInternal', [$this->attributes, $options], true) === false) {
            return false;
        }

        /*
         * Validate attributes before trying to save
         */
        foreach ($this->attributes as $attribute => $value) {
            if (is_array($value)) {
                throw new Exception(sprintf('Unexpected type of array when attempting to save attribute "%s", try adding it to the $jsonable property.', $attribute));
            }
        }

        // Apply pre deferred bindings
        if ($this->sessionKey !== null) {
            $this->commitDeferredBefore($this->sessionKey);
        }

        // Save the record
        $result = parent::save($options);

        // Halted by event
        if ($result === false) {
            return $result;
        }

        /*
         * If there is nothing to update, Eloquent will not fire afterSave(),
         * events should still fire for consistency.
         */
        if ($result === null) {
            $this->fireModelEvent('updated', false);
            $this->fireModelEvent('saved', false);
        }

        // Apply post deferred bindings
        if ($this->sessionKey !== null) {
            $this->commitDeferredAfter($this->sessionKey);
        }

        return $result;
    }

    /**
     * Save the model to the database.
     * @param array $options
     * @param null $sessionKey
     * @return bool
     */
    public function save(array $options = null, $sessionKey = null)
    {
        $this->sessionKey = $sessionKey;
        return $this->saveInternal(['force' => false] + (array) $options);
    }

    /**
     * Save the model and all of its relationships.
     * @param array $options
     * @param null $sessionKey
     * @return bool
     */
    public function push($options = null, $sessionKey = null)
    {
        $always = Arr::get($options, 'always', false);

        if (!$this->save(null, $sessionKey) && !$always) {
            return false;
        }

        foreach ($this->relations as $name => $models) {
            if (!$this->isRelationPushable($name)) {
                continue;
            }

            if ($models instanceof CollectionBase) {
                $models = $models->all();
            }
            elseif ($models instanceof EloquentModel) {
                $models = [$models];
            }
            else {
                $models = (array) $models;
            }

            foreach (array_filter($models) as $model) {
                if (!$model->push(null, $sessionKey)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Pushes the first level of relations even if the parent
     * model has no changes.
     * @param array $options
     * @param string $sessionKey
     * @return bool
     */
    public function alwaysPush($options = null, $sessionKey)
    {
        return $this->push(['always' => true] + (array) $options, $sessionKey);
    }

    //
    // Deleting
    //

    /**
     * Perform the actual delete query on this model instance.
     * @return void
     */
    protected function performDeleteOnModel()
    {
        $this->performDeleteOnRelations();

        $this->setKeysForSaveQuery($this->newQueryWithoutScopes())->delete();
    }

    /**
     * Locates relations with delete flag and cascades the delete event.
     * @return void
     */
    protected function performDeleteOnRelations()
    {
        $definitions = $this->getRelationDefinitions();
        foreach ($definitions as $type => $relations) {
            /*
             * Hard 'delete' definition
             */
            foreach ($relations as $name => $options) {
                if (!Arr::get($options, 'delete', false)) {
                    continue;
                }

                if (!$relation = $this->{$name}) {
                    continue;
                }

                if ($relation instanceof EloquentModel) {
                    $relation->forceDelete();
                }
                elseif ($relation instanceof CollectionBase) {
                    $relation->each(function($model) {
                        $model->forceDelete();
                    });
                }
            }

            /*
             * Belongs-To-Many should clean up after itself always
             */
            if ($type == 'belongsToMany') {
                foreach ($relations as $name => $options) {
                    $this->{$name}()->detach();
                }
            }
        }
    }

    //
    // Adders
    //

    /**
     * Add attribute casts for the model.
     *
     * @param  array $attributes
     * @return void
     */
    public function addCasts($attributes)
    {
        $this->casts = array_merge($this->casts, $attributes);
    }

    /**
     * Adds a datetime attribute to convert to an instance of Carbon/DateTime object.
     * @param string   $attribute
     * @return void
     */
    public function addDateAttribute($attribute)
    {
        if (in_array($attribute, $this->dates)) {
            return;
        }

        $this->dates[] = $attribute;
    }

    /**
     * Add fillable attributes for the model.
     *
     * @param  array|string|null  $attributes
     * @return void
     */
    public function addFillable($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->fillable = array_merge($this->fillable, $attributes);
    }

    /**
     * Add jsonable attributes for the model.
     *
     * @param  array|string|null  $attributes
     * @return void
     */
    public function addJsonable($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->jsonable = array_merge($this->jsonable, $attributes);
    }

    //
    // Getters
    //

    /**
     * Get an attribute from the model.
     * Overrided from {@link Eloquent} to implement recognition of the relation.
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->attributes) || $this->hasGetMutator($key)) {
            return $this->getAttributeValue($key);
        }

        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }

        if ($this->hasRelation($key)) {
            return $this->getRelationshipFromMethod($key);
        }
    }

    /**
     * Get a plain attribute (not a relationship).
     * @param  string  $key
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        /**
         * @event model.beforeGetAttribute
         * Called before the model attribute is retrieved
         *
         * Example usage:
         *
         *     $model->bindEvent('model.beforeGetAttribute', function ((string) $key) use (\October\Rain\Database\Model $model) {
         *         if ($key === 'not-for-you-to-look-at') {
         *             return 'you are not allowed here';
         *         }
         *     });
         *
         */
        if (($attr = $this->fireEvent('model.beforeGetAttribute', [$key], true)) !== null) {
            return $attr;
        }

        $attr = parent::getAttributeValue($key);

        /*
         * Return valid json (boolean, array) if valid, otherwise
         * jsonable fields will return a string for invalid data.
         */
        if ($this->isJsonable($key) && !empty($attr)) {
            $_attr = json_decode($attr, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $attr = $_attr;
            }
        }

        /**
         * @event model.getAttribute
         * Called after the model attribute is retrieved
         *
         * Example usage:
         *
         *     $model->bindEvent('model.getAttribute', function ((string) $key, $value) use (\October\Rain\Database\Model $model) {
         *         if ($key === 'not-for-you-to-look-at') {
         *             return "Totally not $value";
         *         }
         *     });
         *
         */
        if (($_attr = $this->fireEvent('model.getAttribute', [$key, $attr], true)) !== null) {
            return $_attr;
        }

        return $attr;
    }

    /**
     * Determine if a get mutator exists for an attribute.
     * @param  string  $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        return $this->methodExists('get'.Str::studly($key).'Attribute');
    }

    /**
     * Convert the model's attributes to an array.
     * @return array
     */
    public function attributesToArray()
    {
        $attributes = $this->getArrayableAttributes();

        /*
         * Before Event
         */
        foreach ($attributes as $key => $value) {
            if (($eventValue = $this->fireEvent('model.beforeGetAttribute', [$key], true)) !== null) {
                $attributes[$key] = $eventValue;
            }
        }

        /*
         * Dates
         */
        foreach ($this->getDates() as $key) {
            if (!isset($attributes[$key])) {
                continue;
            }

            $attributes[$key] = $this->serializeDate(
                $this->asDateTime($attributes[$key])
            );
        }

        /*
         * Mutate
         */
        $mutatedAttributes = $this->getMutatedAttributes();

        foreach ($mutatedAttributes as $key) {
            if (!array_key_exists($key, $attributes)) {
                continue;
            }

            $attributes[$key] = $this->mutateAttributeForArray(
                $key, $attributes[$key]
            );
        }

        /*
         * Casts
         */
        foreach ($this->casts as $key => $value) {
            if (
                !array_key_exists($key, $attributes) ||
                in_array($key, $mutatedAttributes)
            ) {
                continue;
            }

            $attributes[$key] = $this->castAttribute(
                $key, $attributes[$key]
            );
        }

        /*
         * Appends
         */
        foreach ($this->getArrayableAppends() as $key) {
            $attributes[$key] = $this->mutateAttributeForArray($key, null);
        }

        /*
         * Jsonable
         */
        foreach ($this->jsonable as $key) {
            if (
                !array_key_exists($key, $attributes) ||
                in_array($key, $mutatedAttributes)
            ) {
                continue;
            }

            $jsonValue = json_decode($attributes[$key], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $attributes[$key] = $jsonValue;
            }
        }

        /*
         * After Event
         */
        foreach ($attributes as $key => $value) {
            if (($eventValue = $this->fireEvent('model.getAttribute', [$key, $value], true)) !== null) {
                $attributes[$key] = $eventValue;
            }
        }

        return $attributes;
    }

    //
    // Setters
    //

    /**
     * Set a given attribute on the model.
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        /*
         * Attempting to set attribute [null] on model.
         */
        if (empty($key)) {
            throw new Exception('Cannot access empty model attribute.');
        }

        /*
         * Handle direct relation setting
         */
        if ($this->hasRelation($key)) {
            return $this->setRelationValue($key, $value);
        }

        /**
         * @event model.beforeSetAttribute
         * Called before the model attribute is set
         *
         * Example usage:
         *
         *     $model->bindEvent('model.beforeSetAttribute', function ((string) $key, $value) use (\October\Rain\Database\Model $model) {
         *         if ($key === 'not-for-you-to-touch') {
         *             return '$value has been touched! The humanity!';
         *         }
         *     });
         *
         */
        if (($_value = $this->fireEvent('model.beforeSetAttribute', [$key, $value], true)) !== null) {
            $value = $_value;
        }

        /*
         * Jsonable
         */
        if ($this->isJsonable($key) && (!empty($value) || is_array($value))) {
            $value = json_encode($value);
        }

        /*
         * Trim scalars
         */
        if (
            !is_object($value) &&
            !is_array($value) &&
            !is_null($value) &&
            !is_bool($value)
        ) {
            $value = trim($value);
        }

        $result = parent::setAttribute($key, $value);

        /**
         * @event model.setAttribute
         * Called after the model attribute is set
         *
         * Example usage:
         *
         *     $model->bindEvent('model.setAttribute', function ((string) $key, $value) use (\October\Rain\Database\Model $model) {
         *         if ($key === 'not-for-you-to-touch') {
         *             \Log::info("{$key} has been touched and set to {$value}!")
         *         }
         *     });
         *
         */
        $this->fireEvent('model.setAttribute', [$key, $value]);

        return $result;
    }

    /**
     * Determine if a set mutator exists for an attribute.
     * @param  string  $key
     * @return bool
     */
    public function hasSetMutator($key)
    {
        return $this->methodExists('set'.Str::studly($key).'Attribute');
    }
}
