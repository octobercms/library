<?php namespace October\Rain\Database;

use Db;
use Input;
use Closure;
use October\Rain\Support\Arr;
use October\Rain\Support\Str;
use October\Rain\Argon\Argon;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as CollectionBase;
use October\Rain\Database\Relations\BelongsTo;
use October\Rain\Database\Relations\BelongsToMany;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Relations\HasOne;
use October\Rain\Database\Relations\MorphMany;
use October\Rain\Database\Relations\MorphToMany;
use October\Rain\Database\Relations\MorphTo;
use October\Rain\Database\Relations\MorphOne;
use October\Rain\Database\Relations\AttachMany;
use October\Rain\Database\Relations\AttachOne;
use October\Rain\Database\Relations\HasManyThrough;
use InvalidArgumentException;
use Exception;
use DateTime;

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
     * Cleaner declaration of relationships.
     * Uses a similar approach to the relation methods used by Eloquent, but as separate properties
     * that make the class file less cluttered.
     *
     * It should be declared with keys as the relation name, and value being a mixed array.
     * The relation type $morphTo does not include a classname as the first value.
     *
     * Example:
     * class Order extends Model
     * {
     *     protected $hasMany = [
     *         'items' => 'Item'
     *     ];
     * }
     * @var array
     */
    public $hasMany = [];

    /**
     * protected $hasOne = [
     *     'owner' => ['User', 'key' => 'user_id']
     * ];
     */
    public $hasOne = [];

    /**
     * protected $belongsTo = [
     *     'parent' => ['Category', 'key' => 'parent_id']
     * ];
     */
    public $belongsTo = [];

    /**
     * protected $belongsToMany = [
     *     'groups' => ['Group', 'table'=> 'join_groups_users']
     * ];
     */
    public $belongsToMany = [];

    /**
     * protected $morphTo = [
     *     'pictures' => []
     * ];
     */
    public $morphTo = [];

    /**
     * protected $morphOne = [
     *     'log' => ['History', 'name' => 'user']
     * ];
     */
    public $morphOne = [];

    /**
     * protected $morphMany = [
     *     'log' => ['History', 'name' => 'user']
     * ];
     */
    public $morphMany = [];

    /**
     * protected $morphToMany = [
     *     'tag' => ['Tag', 'table' => 'tagables', 'name' => 'tagable']
     * ];
     */
    public $morphToMany = [];

    public $morphedByMany = [];

    /**
     * protected $attachOne = [
     *     'picture' => ['October\Rain\Database\Attach\File', 'public' => false]
     * ];
     */
    public $attachOne = [];

    /**
     * protected $attachMany = [
     *     'pictures' => ['October\Rain\Database\Attach\File', 'name'=> 'imageable']
     * ];
     */
    public $attachMany = [];

    /**
     * protected $attachMany = [
     *     'pictures' => ['Picture', 'name'=> 'imageable']
     * ];
     */
    public $hasManyThrough = [];

    /**
     * @var array Excepted relationship types, used to cycle and verify relationships.
     */
    protected static $relationTypes = ['hasOne', 'hasMany', 'belongsTo', 'belongsToMany', 'morphTo', 'morphOne', 'morphMany', 'morphToMany', 'morphedByMany', 'attachOne', 'attachMany', 'hasManyThrough'];

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

        if ($value instanceof DateTime) {
            return Argon::instance($value);
        }

        if (is_numeric($value)) {
            return Argon::createFromTimestamp($value);
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            return Argon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        return Argon::createFromFormat($this->getDateFormat(), $value);
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
     * This a custom piece of logic specifically to satisfy Twig's
     * desire to return a relation object instead of loading the
     * related model.
     *
     * @param  mixed  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        if ($result = isset($this->$offset)) {
            return $result;
        }

        return $this->hasRelation($offset);
    }

    //
    // Relations
    //

    /**
     * Checks if model has a relationship by supplied name.
     * @param string $name Relation name
     * @return bool
     */
    public function hasRelation($name)
    {
        return $this->getRelationDefinition($name) !== null ? true : false;
    }

    /**
     * Returns relationship details from a supplied name.
     * @param string $name Relation name
     * @return array
     */
    public function getRelationDefinition($name)
    {
        if (($type = $this->getRelationType($name)) !== null) {
            return (array) $this->{$type}[$name] + $this->getRelationDefaults($type);
        }
    }

    /**
     * Returns relationship details for all relations defined on this model.
     * @return array
     */
    public function getRelationDefinitions()
    {
        $result = [];

        foreach (static::$relationTypes as $type) {
            $result[$type] = $this->{$type};

            /*
             * Apply default values for the relation type
             */
            if ($defaults = $this->getRelationDefaults($type)) {
                foreach ($result[$type] as $relation => $options) {
                    $result[$type][$relation] = (array) $options + $defaults;
                }
            }
        }

        return $result;
    }

    /**
     * Returns a relationship type based on a supplied name.
     * @param string $name Relation name
     * @return string
     */
    public function getRelationType($name)
    {
        foreach (static::$relationTypes as $type) {
            if (isset($this->{$type}[$name])) {
                return $type;
            }
        }
    }

    /**
     * Returns a relation class object
     * @param string $name Relation name
     * @return string
     */
    public function makeRelation($name)
    {
        $relationType = $this->getRelationType($name);
        $relation = $this->getRelationDefinition($name);

        if ($relationType == 'morphTo' || !isset($relation[0])) {
            return null;
        }

        $relationClass = $relation[0];
        return new $relationClass();
    }

    /**
     * Determines whether the specified relation should be saved
     * when push() is called instead of save() on the model. Default: true.
     * @param  string  $name Relation name
     * @return boolean
     */
    public function isRelationPushable($name)
    {
        $definition = $this->getRelationDefinition($name);
        if (is_null($definition) || !array_key_exists('push', $definition)) {
            return true;
        }

        return (bool) $definition['push'];
    }

    /**
     * Returns default relation arguments for a given type.
     * @param string $type Relation type
     * @return array
     */
    protected function getRelationDefaults($type)
    {
        switch ($type) {
            case 'attachOne':
            case 'attachMany':
                return ['order' => 'sort_order', 'delete' => true];

            default:
                return [];
        }
    }

    /**
     * Looks for the relation and does the correct magic as Eloquent would require
     * inside relation methods. For more information, read the documentation of the mentioned property.
     * @param string $relationName the relation key, camel-case version
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    protected function handleRelation($relationName)
    {
        $relationType = $this->getRelationType($relationName);
        $relation = $this->getRelationDefinition($relationName);

        if (!isset($relation[0]) && $relationType != 'morphTo') {
            throw new InvalidArgumentException(sprintf(
                "Relation '%s' on model '%s' should have at least a classname.", $relationName, get_called_class()
            ));
        }

        if (isset($relation[0]) && $relationType == 'morphTo') {
            throw new InvalidArgumentException(sprintf(
                "Relation '%s' on model '%s' is a morphTo relation and should not contain additional arguments.", $relationName, get_called_class()
            ));
        }

        switch ($relationType) {
            case 'hasOne':
            case 'hasMany':
                $relation = $this->validateRelationArgs($relationName, ['key', 'otherKey']);
                $relationObj = $this->$relationType($relation[0], $relation['key'], $relation['otherKey'], $relationName);
                break;

            case 'belongsTo':
                $relation = $this->validateRelationArgs($relationName, ['key', 'otherKey']);
                $relationObj = $this->$relationType($relation[0], $relation['key'], $relation['otherKey'], $relationName);
                break;

            case 'belongsToMany':
                $relation = $this->validateRelationArgs($relationName, ['table', 'key', 'otherKey', 'pivot', 'timestamps']);
                $relationObj = $this->$relationType($relation[0], $relation['table'], $relation['key'], $relation['otherKey'], $relationName);
                break;

            case 'morphTo':
                $relation = $this->validateRelationArgs($relationName, ['name', 'type', 'id']);
                $relationObj = $this->$relationType($relation['name'] ?: $relationName, $relation['type'], $relation['id']);
                break;

            case 'morphOne':
            case 'morphMany':
                $relation = $this->validateRelationArgs($relationName, ['type', 'id', 'key'], ['name']);
                $relationObj = $this->$relationType($relation[0], $relation['name'], $relation['type'], $relation['id'], $relation['key'], $relationName);
                break;

            case 'morphToMany':
                $relation = $this->validateRelationArgs($relationName, ['table', 'key', 'otherKey', 'pivot', 'timestamps'], ['name']);
                $relationObj = $this->$relationType($relation[0], $relation['name'], $relation['table'], $relation['key'], $relation['otherKey'], false, $relationName);
                break;

            case 'morphedByMany':
                $relation = $this->validateRelationArgs($relationName, ['table', 'key', 'otherKey', 'pivot', 'timestamps'], ['name']);
                $relationObj = $this->$relationType($relation[0], $relation['name'], $relation['table'], $relation['key'], $relation['otherKey'], $relationName);
                break;

            case 'attachOne':
            case 'attachMany':
                $relation = $this->validateRelationArgs($relationName, ['public', 'key']);
                $relationObj = $this->$relationType($relation[0], $relation['public'], $relation['key'], $relationName);
                break;

            case 'hasManyThrough':
                $relation = $this->validateRelationArgs($relationName, ['key', 'throughKey', 'otherKey'], ['through']);
                $relationObj = $this->$relationType($relation[0], $relation['through'], $relation['key'], $relation['throughKey'], $relation['otherKey']);
                break;

            default:
                throw new InvalidArgumentException(sprintf("There is no such relation type known as '%s' on model '%s'.", $relationType, get_called_class()));
        }

        return $relationObj;
    }

    /**
     * Validate relation supplied arguments.
     */
    protected function validateRelationArgs($relationName, $optional, $required = [])
    {
        $relation = $this->getRelationDefinition($relationName);

        // Query filter arguments
        $filters = ['scope', 'conditions', 'order', 'pivot', 'timestamps', 'push', 'count'];

        foreach (array_merge($optional, $filters) as $key) {
            if (!array_key_exists($key, $relation)) {
                $relation[$key] = null;
            }
        }

        $missingRequired = [];
        foreach ($required as $key) {
            if (!array_key_exists($key, $relation)) {
                $missingRequired[] = $key;
            }
        }

        if ($missingRequired) {
            throw new InvalidArgumentException(sprintf('Relation "%s" on model "%s" should contain the following key(s): %s',
                $relationName,
                get_called_class(),
                join(', ', $missingRequired)
            ));
        }

        return $relation;
    }

    /**
     * Define an polymorphic, inverse one-to-one or many relationship.
     * Overridden from {@link Eloquent\Model} to allow the usage of the intermediary methods to handle the relation.
     * @return \October\Rain\Database\Relations\BelongsTo
     */
    public function morphTo($name = null, $type = null, $id = null)
    {
        if (is_null($name)) {
            $name = snake_case($this->getRelationCaller());
        }

        list($type, $id) = $this->getMorphs($name, $type, $id);

        // If the type value is null it is probably safe to assume we're eager loading
        // the relationship. When that is the case we will pass in a dummy query as
        // there are multiple types in the morph and we can't use single queries.
        if (is_null($class = $this->$type)) {
            return new MorphTo(
                $this->newQuery(), $this, $id, null, $type, $name
            );
        }
        // If we are not eager loading the relationship we will essentially treat this
        // as a belongs-to style relationship since morph-to extends that class and
        // we will pass in the appropriate values so that it behaves as expected.
        else {
            $instance = new $class;

            return new MorphTo(
                $instance->newQuery(), $this, $id, $instance->getKeyName(), $type, $name
            );
        }
    }

    /**
     * Define a one-to-one relationship.
     * This code is a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\HasOne
     */
    public function hasOne($related, $primaryKey = null, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $primaryKey = $primaryKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        $instance = new $related;

        return new HasOne($instance->newQuery(), $this, $instance->getTable().'.'.$primaryKey, $localKey, $relationName);
    }

    /**
     * Define a polymorphic one-to-one relationship.
     * This code is a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\MorphOne
     */
    public function morphOne($related, $name, $type = null, $id = null, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = new $related;

        list($type, $id) = $this->getMorphs($name, $type, $id);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        return new MorphOne($instance->newQuery(), $this, $table.'.'.$type, $table.'.'.$id, $localKey, $relationName);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * Overridden from {@link Eloquent\Model} to allow the usage of the intermediary methods to handle the {@link
     * $relationsData} array.
     * @return \October\Rain\Database\Relations\BelongsTo
     */
    public function belongsTo($related, $foreignKey = null, $parentKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        if (is_null($foreignKey)) {
            $foreignKey = snake_case($relationName).'_id';
        }

        $instance = new $related;

        $query = $instance->newQuery();

        $parentKey = $parentKey ?: $instance->getKeyName();

        return new BelongsTo($query, $this, $foreignKey, $parentKey, $relationName);
    }

    /**
     * Define a one-to-many relationship.
     * This code is a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\HasMany
     */
    public function hasMany($related, $primaryKey = null, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $primaryKey = $primaryKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        $instance = new $related;

        return new HasMany($instance->newQuery(), $this, $instance->getTable().'.'.$primaryKey, $localKey, $relationName);
    }

    /**
     * Define a has-many-through relationship.
     * This code is a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\HasMany
     */
    public function hasManyThrough($related, $through, $primaryKey = null, $throughKey = null, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = new $related;

        $throughInstance = new $through;

        $primaryKey = $primaryKey ?: $this->getForeignKey();

        $throughKey = $throughKey ?: $throughInstance->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return new HasManyThrough($instance->newQuery(), $this, $throughInstance, $primaryKey, $throughKey, $localKey);
    }

    /**
     * Define a polymorphic one-to-many relationship.
     * This code is a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\MorphMany
     */
    public function morphMany($related, $name, $type = null, $id = null, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = new $related;

        list($type, $id) = $this->getMorphs($name, $type, $id);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        return new MorphMany($instance->newQuery(), $this, $table.'.'.$type, $table.'.'.$id, $localKey, $relationName);
    }

    /**
     * Define a many-to-many relationship.
     * This code is almost a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\BelongsToMany
     */
    public function belongsToMany($related, $table = null, $primaryKey = null, $foreignKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $primaryKey = $primaryKey ?: $this->getForeignKey();

        $instance = new $related;

        $foreignKey = $foreignKey ?: $instance->getForeignKey();

        if (is_null($table)) {
            $table = $this->joiningTable($related);
        }

        $query = $instance->newQuery();

        return new BelongsToMany($query, $this, $table, $primaryKey, $foreignKey, $relationName);
    }

    /**
     * Define a polymorphic many-to-many relationship.
     * This code is almost a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\MorphToMany
     */
    public function morphToMany($related, $name, $table = null, $primaryKey = null, $foreignKey = null, $inverse = false, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $primaryKey = $primaryKey ?: $name.'_id';

        $instance = new $related;

        $foreignKey = $foreignKey ?: $instance->getForeignKey();

        if (is_null($table)) {
            $table = $this->joiningTable($related);
        }

        $query = $instance->newQuery();

        return new MorphToMany($query, $this, $name, $table, $primaryKey, $foreignKey, $relationName, $inverse);
    }

    /**
     * Define a polymorphic many-to-many inverse relationship.
     * This code is almost a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\MorphToMany
     */
    public function morphedByMany($related, $name, $table = null, $primaryKey = null, $foreignKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $primaryKey = $primaryKey ?: $this->getForeignKey();

        $foreignKey = $foreignKey ?: $name.'_id';

        return $this->morphToMany($related, $name, $table, $primaryKey, $foreignKey, true, $relationName);
    }

    /**
     * Define an attachment one-to-many relationship.
     * This code is a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\MorphMany
     */
    public function attachMany($related, $isPublic = null, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = new $related;

        list($type, $id) = $this->getMorphs('attachment', null, null);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        return new AttachMany($instance->newQuery(), $this, $table.'.'.$type, $table.'.'.$id, $isPublic, $localKey, $relationName);
    }

    /**
     * Define an attachment one-to-one relationship.
     * This code is a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\MorphOne
     */
    public function attachOne($related, $isPublic = true, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = new $related;

        list($type, $id) = $this->getMorphs('attachment', null, null);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        return new AttachOne($instance->newQuery(), $this, $table.'.'.$type, $table.'.'.$id, $isPublic, $localKey, $relationName);
    }

    /**
     * Finds the calling function name from the stack trace.
     */
    protected function getRelationCaller()
    {
        $backtrace = debug_backtrace(false);
        $caller = ($backtrace[2]['function'] == 'handleRelation') ? $backtrace[4] : $backtrace[2];
        return $caller['function'];
    }

    /**
     * Returns a relation key value(s), not as an object.
     */
    public function getRelationValue($relationName)
    {
        return $this->$relationName()->getSimpleValue();
    }

    /**
     * Sets a relation value directly from its attribute.
     */
    protected function setRelationValue($relationName, $value)
    {
        $this->$relationName()->setSimpleValue($value);
    }

    //
    // Pivot
    //

    /**
     * Create a generic pivot model instance.
     * @param  \October\Rain\Database\Model  $parent
     * @param  array   $attributes
     * @param  string  $table
     * @param  bool    $exists
     * @return \October\Rain\Database\Pivot
     */
    public function newPivot(EloquentModel $parent, array $attributes, $table, $exists)
    {
        return new Pivot($parent, $attributes, $table, $exists);
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
        // Event
        if ($this->fireEvent('model.saveInternal', [$this->attributes, $options], true) === false) {
            return false;
        }

        /*
         * Validate attributes before trying to save
         */
        foreach ($this->attributes as $attribute => $value) {
            if (is_array($value)) {
                throw new Exception(sprintf('Unexpected type of array, should attribute "%s" be jsonable?', $attribute));
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
        /*
         * Before Event
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

        /*
         * After Event
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
         * Handle direct relation setting
         */
        if ($this->hasRelation($key)) {
            return $this->setRelationValue($key, $value);
        }

        /*
         * Before Event
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

        /*
         * After Event
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
