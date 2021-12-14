<?php namespace October\Rain\Halcyon;

use October\Rain\Support\Arr;
use October\Rain\Support\Str;
use October\Rain\Extension\Extendable;
use October\Rain\Halcyon\Datasource\ResolverInterface as Resolver;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Events\Dispatcher;
use BadMethodCallException;
use JsonSerializable;
use ArrayAccess;
use Exception;

/**
 * Model is a base template object, equivalent to a Model in ORM
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class Model extends Extendable implements ArrayAccess, Arrayable, Jsonable, JsonSerializable
{
    use \October\Rain\Support\Traits\Emitter;

    /**
     * @var string datasource is the data source for the model, a directory path.
     */
    protected $datasource;

    /**
     * @var string dirName is the container name associated with the model, eg: pages.
     */
    protected $dirName;

    /**
     * @var array attributes saved to the settings area.
     */
    public $attributes = [];

    /**
     * @var array original attributes.
     */
    protected $original = [];

    /**
     * @var array appends to the model's array form.
     */
    protected $appends = [];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [];

    /**
     * @var array List of attribute names which are not considered "settings".
     */
    protected $purgeable = [];

    /**
     * @var array allowedExtensions is allowable file extensions.
     */
    protected $allowedExtensions = ['htm'];

    /**
     * @var string defaultExtension is default file extension.
     */
    protected $defaultExtension = 'htm';

    /**
     * @var bool isCompoundObject supports code and settings sections.
     */
    protected $isCompoundObject = true;

    /**
     * @var bool wrapCode section in PHP tags.
     */
    protected $wrapCode = true;

    /**
     * @var int maxNesting is the maximum allowed path nesting level. The default value is 5,
     * meaning that files can only exist in the root directory, or in a subdirectory.
     * Set to null if any level is allowed.
     */
    protected $maxNesting = 5;

    /**
     * @var boolean loadedFromCache indicates whether the object was loaded from the cache.
     */
    protected $loadedFromCache = false;

    /**
     * @var array observables are user exposed observable events.
     */
    protected $observables = [];

    /**
     * @var bool exists indicates if the model exists.
     */
    public $exists = false;

    /**
     * @var \Illuminate\Cache\CacheManager cache manager
     */
    protected static $cache;

    /**
     * @var \October\Rain\Halcyon\Datasource\ResolverInterface resolver instance.
     */
    protected static $resolver;

    /**
     * @var \Illuminate\Contracts\Events\Dispatcher dispatcher instance
     */
    protected static $dispatcher;

    /**
     * @var array mutatorCache for each class.
     */
    protected static $mutatorCache = [];

    /**
     * @var array eventsBooted is the array of models booted events.
     */
    protected static $eventsBooted = [];

    /**
     * @var array booted models
     */
    protected static $booted = [];

    /**
     * __construct a new Halcyon model instance.
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->bootIfNotBooted();

        $this->bootNicerEvents();

        parent::__construct();

        $this->syncOriginal();

        $this->fill($attributes);
    }

    /**
     * bootIfNotBooted checks if the model needs to be booted and if so, do it.
     */
    protected function bootIfNotBooted()
    {
        $class = get_class($this);

        if (!isset(static::$booted[$class])) {
            static::$booted[$class] = true;

            $this->fireModelEvent('booting', false);

            static::boot();

            $this->fireModelEvent('booted', false);
        }
    }

    /**
     * boot is the "booting" method of the model.
     */
    protected static function boot()
    {
        static::bootTraits();
    }

    /**
     * bootTraits boots all of the bootable traits on the model.
     */
    protected static function bootTraits()
    {
        foreach (class_uses_recursive(get_called_class()) as $trait) {
            if (method_exists(get_called_class(), $method = 'boot'.class_basename($trait))) {
                forward_static_call([get_called_class(), $method]);
            }
        }
    }

    /**
     * clearBootedModels clears the list of booted models so they will be re-booted.
     */
    public static function clearBootedModels()
    {
        static::$booted = [];
    }

    /**
     * bootNicerEvents binds some nicer events to this model, in the format of method overrides.
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
                if ($radical !== 'fetch') {
                    $method .= 'e';
                }

                self::$eventMethod(function ($model) use ($method) {
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
        static::registerModelEvent('booted', function ($model) {
            $model->fireEvent('model.afterBoot');
            if ($model->methodExists('afterBoot')) {
                return $model->afterBoot();
            }
        });

        static::$eventsBooted[$class] = true;
    }

    /**
     * getIdAttribute is a helper for {{ page.id }} or {{ layout.id }} twig vars
     * Returns a semi-unique string for this object.
     * @return string
     */
    public function getIdAttribute()
    {
        return str_replace('/', '-', $this->getBaseFileNameAttribute());
    }

    /**
     * getBaseFileNameAttribute returns the file name without the extension.
     * @return string
     */
    public function getBaseFileNameAttribute()
    {
        $pos = strrpos($this->fileName, '.');
        if ($pos === false) {
            return $this->fileName;
        }

        return substr($this->fileName, 0, $pos);
    }

    /**
     * addFillable adds fillable attributes for the model.
     * @param  array|string|null  $attributes
     * @return void
     */
    public function addFillable($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->fillable = array_merge($this->fillable, $attributes);
    }

    /**
     * addPurgeable adds an attribute to the purgeable attributes list
     * @param  array|string|null  $attributes
     * @return void
     */
    public function addPurgeable($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->purgeable = array_merge($this->purgeable, $attributes);

        // @deprecated
        return $this;
    }

    /**
     * getSettingsAttribute is the settings is attribute contains everything that should
     * be saved to the settings area.
     * @return array
     */
    public function getSettingsAttribute()
    {
        $defaults = [
            'fileName',
            'components',
            'content',
            'markup',
            'mtime',
            'code'
        ];

        return array_diff_key(
            $this->attributes,
            array_flip(array_merge($defaults, $this->purgeable))
        );
    }

    /**
     * setSettingsAttribute filling the settings should merge it with attributes.
     * @param mixed $value
     */
    public function setSettingsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes = array_merge($this->attributes, $value);
        }
    }

    /**
     * setFileNameAttribute wjere file name should always contain an extension.
     * @param mixed $value
     */
    public function setFileNameAttribute($value)
    {
        $fileName = trim($value);

        if (strlen($fileName) && !strlen(pathinfo($value, PATHINFO_EXTENSION))) {
            $fileName .= '.'.$this->defaultExtension;
        }

        $this->attributes['fileName'] = $fileName;
    }

    /**
     * getObjectTypeDirName returns the directory name corresponding to the object type.
     * For pages the directory name is "pages", for layouts - "layouts", etc.
     * @return string
     */
    public function getObjectTypeDirName()
    {
        return $this->dirName;
    }

    /**
     * getAllowedExtensions returns the allowable file extensions supported by this model.
     * @return array
     */
    public function getAllowedExtensions()
    {
        return $this->allowedExtensions;
    }

    /**
     * isCompoundObject returns true if this template supports code and settings sections.
     * @return bool
     */
    public function isCompoundObject()
    {
        return $this->isCompoundObject;
    }

    /**
     * getWrapCode returns true if the code section will be wrapped in PHP tags.
     * @return bool
     */
    public function getWrapCode()
    {
        return $this->wrapCode;
    }

    /**
     * getMaxNesting returns the maximum directory nesting allowed by this template.
     * @return int
     */
    public function getMaxNesting()
    {
        return $this->maxNesting;
    }

    /**
     * isLoadedFromCache returns true if the object was loaded from the cache.
     * @return boolean
     */
    public function isLoadedFromCache()
    {
        return $this->loadedFromCache;
    }

    /**
     * setLoadedFromCache returns true if the object was loaded from the cache.
     * @return bool
     */
    public function setLoadedFromCache($value)
    {
        $this->loadedFromCache = (bool) $value;
    }

    /**
     * fill the model with an array of attributes.
     * @param  array  $attributes
     * @return $this
     */
    public function fill(array $attributes)
    {
        foreach ($this->fillableFromArray($attributes) as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * fillableFromArray gets the fillable attributes of a given array.
     * @param  array  $attributes
     * @return array
     */
    protected function fillableFromArray(array $attributes)
    {
        $defaults = ['fileName'];

        if (count($this->fillable) > 0) {
            return array_intersect_key(
                $attributes,
                array_flip(array_merge($defaults, $this->fillable))
            );
        }

        return $attributes;
    }

    /**
     * newInstance creates a new instance of the given model.
     * @param  array  $attributes
     * @param  bool  $exists
     * @return static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        // This method just provides a convenient way for us to generate fresh model
        // instances of this current model. It is particularly useful during the
        // hydration of new objects via the Halcyon query builder instances.
        $model = new static((array) $attributes);

        $model->exists = $exists;

        return $model;
    }

    /**
     * newFromBuilder creates a new model instance that is existing.
     * @param  array  $attributes
     * @param  string|null  $datasource
     * @return static
     */
    public function newFromBuilder($attributes = [], $datasource = null)
    {
        $instance = $this->newInstance([], true);

        if ($instance->fireModelEvent('fetching') === false) {
            return $instance;
        }

        $instance->setRawAttributes((array) $attributes, true);

        $instance->fireModelEvent('fetched', false);

        $instance->setDatasource($datasource ?: $this->datasource);

        return $instance;
    }

    /**
     * hydrate creates a collection of models from plain arrays.
     * @param  array  $items
     * @param  string|null  $datasource
     * @return \October\Rain\Halcyon\Collection
     */
    public static function hydrate(array $items, $datasource = null)
    {
        $instance = (new static)->setDatasource($datasource);

        $items = array_map(function ($item) use ($instance) {
            return $instance->newFromBuilder($item);
        }, $items);

        return $instance->newCollection($items);
    }

    /**
     * create saves a new model and return the instance.
     * @param  array  $attributes
     * @return static
     */
    public static function create(array $attributes = [])
    {
        $model = new static($attributes);

        $model->save();

        return $model;
    }

    /**
     * query begins querying the model.
     * @return \October\Rain\Halcyon\Builder
     */
    public static function query()
    {
        return (new static)->newQuery();
    }

    /**
     * on begins querying the model on a given datasource.
     * @param  string|null  $datasource
     * @return \October\Rain\Halcyon\Model
     */
    public static function on($datasource = null)
    {
        // First we will just create a fresh instance of this model, and then we can
        // set the datasource on the model so that it is be used for the queries.
        $instance = new static;

        $instance->setDatasource($datasource);

        return $instance;
    }

    /**
     * all of the models from the datasource.
     * @return \October\Rain\Halcyon\Collection|static[]
     */
    public static function all()
    {
        $instance = new static;

        return $instance->newQuery()->get();
    }

    /**
     * isFillable determines if the given attribute may be mass assigned.
     * @param  string  $key
     * @return bool
     */
    public function isFillable($key)
    {
        // File name is always treated as a fillable attribute.
        if ($key === 'fileName') {
            return true;
        }

        // If the key is in the "fillable" array, we can of course assume that it's
        // a fillable attribute. Otherwise, we will check the guarded array when
        // we need to determine if the attribute is black-listed on the model.
        if (in_array($key, $this->fillable)) {
            return true;
        }

        return empty($this->fillable) && !Str::startsWith($key, '_');
    }

    /**
     * toJson converts the model instance to JSON.
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * jsonSerialize converts the object into something JSON serializable.
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * toArray converts the model instance to an array.
     * @return array
     */
    public function toArray()
    {
        return $this->attributesToArray();
    }

    /**
     * attributesToArray converts the model's attributes to an array.
     * @return array
     */
    public function attributesToArray()
    {
        $attributes = $this->attributes;

        $mutatedAttributes = $this->getMutatedAttributes();

        // We want to spin through all the mutated attributes for this model and call
        // the mutator for the attribute. We cache off every mutated attributes so
        // we don't have to constantly check on attributes that actually change.
        foreach ($mutatedAttributes as $key) {
            if (!array_key_exists($key, $attributes)) {
                continue;
            }

            $attributes[$key] = $this->mutateAttributeForArray(
                $key,
                $attributes[$key]
            );
        }

        // Here we will grab all of the appended, calculated attributes to this model
        // as these attributes are not really in the attributes array, but are run
        // when we need to array or JSON the model for convenience to the coder.
        foreach ($this->getArrayableAppends() as $key) {
            $attributes[$key] = $this->mutateAttributeForArray($key, null);
        }

        return $attributes;
    }

    /**
     * getArrayableAppends gets all of the appendable values that are arrayable.
     * @return array
     */
    protected function getArrayableAppends()
    {
        $defaults = ['settings'];

        if (!count($this->appends)) {
            return $defaults;
        }

        return array_merge($defaults, $this->appends);
    }

    /**
     * getAttribute gets a plain attribute.
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        // Before Event
        if (($attr = $this->fireEvent('model.beforeGetAttribute', [$key], true)) !== null) {
            return $attr;
        }

        $value = $this->getAttributeFromArray($key);

        // If the attribute has a get mutator, we will call that then return what
        // it returns as the value, which is useful for transforming values on
        // retrieval from the model to a form that is more useful for usage.
        if ($this->hasGetMutator($key)) {
            return $this->mutateAttribute($key, $value);
        }

        // After Event
        if (($_attr = $this->fireEvent('model.getAttribute', [$key, $value], true)) !== null) {
            return $_attr;
        }

        return $value;
    }

    /**
     * getAttributeFromArray gets an attribute from the $attributes array.
     * @param  string  $key
     * @return mixed
     */
    protected function getAttributeFromArray($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
    }

    /**
     * hasGetMutator determines if a get mutator exists for an attribute.
     * @param  string  $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        return $this->methodExists('get'.Str::studly($key).'Attribute');
    }

    /**
     * mutateAttribute gets the value of an attribute using its mutator.
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function mutateAttribute($key, $value)
    {
        return $this->{'get'.Str::studly($key).'Attribute'}($value);
    }

    /**
     * mutateAttributeForArray gets the value of an attribute using its mutator for array conversion.
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function mutateAttributeForArray($key, $value)
    {
        $value = $this->mutateAttribute($key, $value);

        return $value instanceof Arrayable ? $value->toArray() : $value;
    }

    /**
     * setAttribute sets a given attribute on the model.
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        // Before Event
        if (($_value = $this->fireEvent('model.beforeSetAttribute', [$key, $value], true)) !== null) {
            $value = $_value;
        }

        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the attribute as it is set on
        // the model, such as "json_encoding" an listing of data for storage.
        if ($this->hasSetMutator($key)) {
            $method = 'set'.Str::studly($key).'Attribute';
            // If we return the returned value of the mutator call straight away, that will disable the firing of
            // 'model.setAttribute' event, and then no third party plugins will be able to implement any kind of
            // post processing logic when an attribute is set with explicit mutators. Returning from the mutator
            // call will also break method chaining as intended by returning `$this` at the end of this method.
            $this->{$method}($value);
        }
        else {
            $this->attributes[$key] = $value;
        }

        // After Event
        $this->fireEvent('model.setAttribute', [$key, $value]);

        return $this;
    }

    /**
     * hasSetMutator determines if a set mutator exists for an attribute.
     * @param  string  $key
     * @return bool
     */
    public function hasSetMutator($key)
    {
        return $this->methodExists('set'.Str::studly($key).'Attribute');
    }

    /**
     * getAttributes gets all of the current attributes on the model.
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * setRawAttributes sets the array of model attributes. No checking is done.
     * @param  array  $attributes
     * @param  bool  $sync
     * @return $this
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        $this->attributes = $attributes;

        if ($sync) {
            $this->syncOriginal();
        }

        return $this;
    }

    /**
     * getOriginal gets the model's original attribute values.
     * @param  string|null  $key
     * @param  mixed  $default
     * @return array
     */
    public function getOriginal($key = null, $default = null)
    {
        return Arr::get($this->original, $key, $default);
    }

    /**
     * syncOriginal attributes with the current.
     * @return $this
     */
    public function syncOriginal()
    {
        $this->original = $this->attributes;

        return $this;
    }

    /**
     * syncOriginalAttribute syncs a single original attribute with its current value.
     * @param  string  $attribute
     * @return $this
     */
    public function syncOriginalAttribute($attribute)
    {
        $this->original[$attribute] = $this->attributes[$attribute];

        return $this;
    }

    /**
     * isDirty determines if the model or given attribute(s) have been modified.
     * @param  array|string|null  $attributes
     * @return bool
     */
    public function isDirty($attributes = null)
    {
        $dirty = $this->getDirty();

        if (is_null($attributes)) {
            return count($dirty) > 0;
        }

        if (!is_array($attributes)) {
            $attributes = func_get_args();
        }

        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $dirty)) {
                return true;
            }
        }

        return false;
    }

    /**
     * getDirty get the attributes that have been changed since last sync.
     * @return array
     */
    public function getDirty()
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original)) {
                $dirty[$key] = $value;
            }
            elseif (
                $value !== $this->original[$key] &&
                !$this->originalIsNumericallyEquivalent($key)
            ) {
                $dirty[$key] = $value;
            }
        }

        foreach ($this->original as $key => $value) {
            if (!array_key_exists($key, $this->attributes)) {
                $dirty[$key] = null;
            }
        }

        return $dirty;
    }

    /**
     * originalIsNumericallyEquivalent determine if the new and old values for a given key are
     * numerically equivalent.
     * @param  string  $key
     * @return bool
     */
    protected function originalIsNumericallyEquivalent($key)
    {
        $current = $this->attributes[$key];

        $original = $this->original[$key];

        return is_numeric($current) && is_numeric($original) && strcmp((string) $current, (string) $original) === 0;
    }

    /**
     * delete the model from the database.
     * @return bool|null
     * @throws \Exception
     */
    public function delete()
    {
        if (is_null($this->fileName)) {
            throw new Exception('No file name (fileName) defined on model.');
        }

        if ($this->exists) {
            if ($this->fireModelEvent('deleting') === false) {
                return false;
            }

            $this->performDeleteOnModel();

            $this->exists = false;

            // Once the model has been deleted, we will fire off the deleted event so that
            // the developers may hook into post-delete operations. We will then return
            // a boolean true as the delete is presumably successful on the database.
            $this->fireModelEvent('deleted', false);

            return true;
        }
    }

    /**
     * performDeleteOnModel performs the actual delete query on this model instance.
     */
    protected function performDeleteOnModel()
    {
        $this->newQuery()->delete($this->fileName);
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
     * Remove all of the event listeners for the model.
     *
     * @return void
     */
    public static function flushEventListeners()
    {
        if (!isset(static::$dispatcher)) {
            return;
        }

        $instance = new static;

        foreach ($instance->getObservableEvents() as $event) {
            static::$dispatcher->forget("halcyon.{$event}: ".get_called_class());
        }

        static::$eventsBooted = [];
    }

    /**
     * Register a model event with the dispatcher.
     *
     * @param  string  $event
     * @param  \Closure|string  $callback
     * @param  int  $priority
     * @return void
     */
    protected static function registerModelEvent($event, $callback, $priority = 0)
    {
        if (isset(static::$dispatcher)) {
            $name = get_called_class();

            static::$dispatcher->listen("halcyon.{$event}: {$name}", $callback, $priority);
        }
    }

    /**
     * Get the observable event names.
     *
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
     * Set the observable event names.
     *
     * @param  array  $observables
     * @return $this
     */
    public function setObservableEvents(array $observables)
    {
        $this->observables = $observables;

        return $this;
    }

    /**
     * Add an observable event name.
     *
     * @param  array|mixed  $observables
     * @return void
     */
    public function addObservableEvents($observables)
    {
        $observables = is_array($observables) ? $observables : func_get_args();

        $this->observables = array_unique(array_merge($this->observables, $observables));
    }

    /**
     * Remove an observable event name.
     *
     * @param  array|mixed  $observables
     * @return void
     */
    public function removeObservableEvents($observables)
    {
        $observables = is_array($observables) ? $observables : func_get_args();

        $this->observables = array_diff($this->observables, $observables);
    }

    /**
     * Update the model in the database.
     *
     * @param  array  $attributes
     * @return bool|int
     */
    public function update(array $attributes = [])
    {
        if (!$this->exists) {
            return $this->newQuery()->update($attributes);
        }

        return $this->fill($attributes)->save();
    }

    /**
     * Save the model to the datasource.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = null)
    {
        return $this->saveInternal(['force' => false] + (array) $options);
    }

    /**
     * Save the model to the database. Is used by {@link save()} and {@link forceSave()}.
     * @param array $options
     * @return bool
     */
    public function saveInternal(array $options = [])
    {
        // Event
        if ($this->fireEvent('model.saveInternal', [$this->attributes, $options], true) === false) {
            return false;
        }

        $query = $this->newQuery();

        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This provides a chance for any
        // listeners to cancel save operations if validations fail or whatever.
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        if ($this->exists) {
            $saved = $this->performUpdate($query, $options);
        }
        else {
            $saved = $this->performInsert($query, $options);
        }

        if ($saved) {
            $this->finishSave($options);
        }

        return $saved;
    }

    /**
     * Finish processing on a successful save operation.
     *
     * @param  array  $options
     * @return void
     */
    protected function finishSave(array $options)
    {
        $this->fireModelEvent('saved', false);

        $this->mtime = $this->newQuery()->lastModified();

        $this->syncOriginal();
    }

    /**
     * Perform a model update operation.
     *
     * @param  October\Rain\Halcyon\Builder  $query
     * @param  array  $options
     * @return bool
     */
    protected function performUpdate(Builder $query, array $options = [])
    {
        $dirty = $this->getDirty();

        if (count($dirty) > 0) {
            // If the updating event returns false, we will cancel the update operation so
            // developers can hook Validation systems into their models and cancel this
            // operation if the model does not pass validation. Otherwise, we update.
            if ($this->fireModelEvent('updating') === false) {
                return false;
            }

            // Recheck dirty attributes as they may have change from the updating event
            $dirty = $this->getDirty();

            if (count($dirty) > 0) {
                $query->update($dirty);

                $this->fireModelEvent('updated', false);
            }
        }

        return true;
    }

    /**
     * Perform a model insert operation.
     *
     * @param  October\Rain\Halcyon\Builder  $query
     * @param  array  $options
     * @return bool
     */
    protected function performInsert(Builder $query, array $options = [])
    {
        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        // Ensure the settings attribute is passed through so this distinction
        // is recognised, mainly by the processor.
        $attributes = $this->attributesToArray();

        $query->insert($attributes);

        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
        $this->exists = true;

        $this->fireModelEvent('created', false);

        return true;
    }

    /**
     * Fire the given event for the model.
     *
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
        $event = "halcyon.{$event}: ".get_class($this);

        $method = $halt ? 'until' : 'fire';

        return static::$dispatcher->$method($event, $this);
    }

    /**
     * Get a new query builder for the object
     * @return \October\Rain\Halcyon\Builder
     */
    public function newQuery()
    {
        $datasource = $this->getDatasource();

        $query = new Builder($datasource, $datasource->getPostProcessor());

        return $query->setModel($this);
    }

    /**
     * Create a new Halcyon Collection instance.
     *
     * @param  array  $models
     * @return \October\Rain\Halcyon\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * getFileNameParts returns the base file name and extension.
     * Applies a default extension, if none found.
     */
    public function getFileNameParts($fileName = null)
    {
        if ($fileName === null) {
            $fileName = $this->fileName;
        }

        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        if (!strlen($extension)) {
            $extension = $this->defaultExtension;
            $baseFile = (string) $fileName;
        }
        else {
            $pos = strrpos($fileName, '.');
            $baseFile = substr($fileName, 0, $pos);
        }

        return [$baseFile, $extension];
    }

    /**
     * Get the datasource for the model.
     *
     * @return \October\Rain\Halcyon\Datasource\DatasourceInterface
     */
    public function getDatasource()
    {
        return static::resolveDatasource($this->datasource);
    }

    /**
     * Get the current datasource name for the model.
     *
     * @return string
     */
    public function getDatasourceName()
    {
        return $this->datasource;
    }

    /**
     * Set the datasource associated with the model.
     *
     * @param  string  $name
     * @return $this
     */
    public function setDatasource($name)
    {
        $this->datasource = $name;

        return $this;
    }

    /**
     * Resolve a datasource instance.
     *
     * @param  string|null  $datasource
     * @return \October\Rain\Halcyon\Datasource
     */
    public static function resolveDatasource($datasource = null)
    {
        return static::$resolver->datasource($datasource);
    }

    /**
     * Get the datasource resolver instance.
     *
     * @return \October\Rain\Halcyon\DatasourceResolverInterface
     */
    public static function getDatasourceResolver()
    {
        return static::$resolver;
    }

    /**
     * Set the datasource resolver instance.
     *
     * @param  \October\Rain\Halcyon\Datasource\ResolverInterface  $resolver
     * @return void
     */
    public static function setDatasourceResolver(Resolver $resolver)
    {
        static::$resolver = $resolver;
    }

    /**
     * Unset the datasource resolver for models.
     *
     * @return void
     */
    public static function unsetDatasourceResolver()
    {
        static::$resolver = null;
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public static function getEventDispatcher()
    {
        return static::$dispatcher;
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @return void
     */
    public static function setEventDispatcher(Dispatcher $dispatcher)
    {
        static::$dispatcher = $dispatcher;
    }

    /**
     * Unset the event dispatcher for models.
     *
     * @return void
     */
    public static function unsetEventDispatcher()
    {
        static::$dispatcher = null;
    }

    /**
     * Get the cache manager instance.
     *
     * @return \Illuminate\Cache\CacheManager
     */
    public static function getCacheManager()
    {
        return static::$cache;
    }

    /**
     * Set the cache manager instance.
     *
     * @param  \Illuminate\Cache\CacheManager  $cache
     * @return void
     */
    public static function setCacheManager($cache)
    {
        static::$cache = $cache;
    }

    /**
     * Unset the cache manager for models.
     *
     * @return void
     */
    public static function unsetCacheManager()
    {
        static::$cache = null;
    }

    /**
     * Initializes the object properties from the cached data. The extra data
     * set here becomes available as attributes set on the model after fetch.
     * @param array $cached The cached data array.
     */
    public static function initCacheItem(&$item)
    {
    }

    /**
     * Get the mutated attributes for a given instance.
     *
     * @return array
     */
    public function getMutatedAttributes()
    {
        $class = get_class($this);

        if (!isset(static::$mutatorCache[$class])) {
            static::cacheMutatedAttributes($class);
        }

        return static::$mutatorCache[$class];
    }

    /**
     * Extract and cache all the mutated attributes of a class.
     *
     * @param  string  $class
     * @return void
     */
    public static function cacheMutatedAttributes($class)
    {
        $mutatedAttributes = [];

        // Here we will extract all of the mutated attributes so that we can quickly
        // spin through them after we export models to their array form, which we
        // need to be fast. This'll let us know the attributes that can mutate.
        if (preg_match_all('/(?<=^|;)get([^;]+?)Attribute(;|$)/', implode(';', get_class_methods($class)), $matches)) {
            foreach ($matches[1] as $match) {
                $mutatedAttributes[] = lcfirst($match);
            }
        }

        static::$mutatorCache[$class] = $mutatedAttributes;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        if ($this->extendableIsSettingDynamicProperty()) {
            $this->{$key} = $value;
        }
        else {
            $this->setAttribute($key, $value);
        }
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  mixed  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

    /**
     * Determine if an attribute exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]) ||
            (
                $this->hasGetMutator($key) &&
                !is_null($this->getAttribute($key))
            );
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        try {
            return parent::__call($method, $parameters);
        }
        catch (BadMethodCallException $ex) {
            $query = $this->newQuery();
            return call_user_func_array([$query, $method], $parameters);
        }
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = new static;

        return call_user_func_array([$instance, $method], $parameters);
    }

    /**
     * Convert the model to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}
