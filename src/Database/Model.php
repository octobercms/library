<?php namespace October\Rain\Database;

use Input;
use Closure;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection;
use October\Rain\Database\Relations\BelongsTo;
use October\Rain\Database\Relations\BelongsToMany;
use October\Rain\Database\Relations\HasMany;
use October\Rain\Database\Relations\HasOne;
use October\Rain\Database\Relations\MorphMany;
use October\Rain\Database\Relations\MorphToMany;
use October\Rain\Database\Relations\MorphOne;
use October\Rain\Database\Relations\AttachMany;
use October\Rain\Database\Relations\AttachOne;
use October\Rain\Database\Relations\hasManyThrough;
use October\Rain\Database\ModelException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use InvalidArgumentException;
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
     *     'owner' => ['User', 'foreignKey'=>'user_id']
     * ];
     */
    public $hasOne = [];

    /**
     * protected $belongsTo = [
     *     'parent' => ['Category', 'foreignKey' => 'parent_id']
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
     * Constructor
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct();
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
    public static function create(array $attributes, $sessionKey = null)
    {
        $model = new static($attributes);
        $model->save(null, $sessionKey);
        return $model;
    }

    /**
     * Reloads the model from the database.
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function reload()
    {
        if (!$this->exists) {
            $this->syncOriginal();
        }
        elseif ($fresh = static::find($this->getKey())) {
            $this->setRawAttributes($fresh->getAttributes(), true);
        }

        return $this;
    }

    /**
     * The "booting" method of the model.
     * Overrided to attach before/after method hooks into the model events.
     * @see \Illuminate\Database\Eloquent\Model::boot()
     * @return void
     */
    public static function boot()
    {
        parent::boot();
        self::bootNicerEvents();
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
    private static function bootNicerEvents()
    {
        $self = get_called_class();
        $radicals = ['creat', 'sav', 'updat', 'delet', 'fetch'];
        $hooks = ['before' => 'ing', 'after' => 'ed'];

        foreach ($radicals as $radical) {
            foreach ($hooks as $hook => $event) {

                $eventMethod = $radical . $event; // saving / saved
                $method = $hook . ucfirst($radical); // beforeSave / afterSave
                if ($radical != 'fetch') $method .= 'e';

                self::$eventMethod(function($model) use ($self, $method) {
                    $model->fireEvent('model.' . $method);

                    if ($model->methodExists($method))
                        return $model->$method();
                });
            }
        }

        /*
         * Hook to boot events
         */
        static::registerModelEvent('booted', function($model){
            $model->fireEvent('model.afterBoot');
            if ($model->methodExists('afterBoot'))
                return $model->afterBoot();
        });
    }

    /**
     * Create a new model instance that is existing.
     * @param  array  $attributes
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function newFromBuilder($attributes = [])
    {
        $instance = $this->newInstance([], true);
        if ($instance->fireModelEvent('fetching') === false)
            return $instance;

        $instance->setRawAttributes((array) $attributes, true);

        $instance->fireModelEvent('fetched', false);

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
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @return \October\Rain\Database\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
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

    public function __call($name, $params = null)
    {
        /*
         * Never call handleRelation() anywhere else as it could
         * break getRelationCaller(), use $this->{$name}() instead
         */
        if ($this->hasRelation($name))
            return $this->handleRelation($name);

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
        if ($result = isset($this->$offset))
            return $result;

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
            return $this->{$type}[$name];
        }
    }

    /**
     * Returns a relationship type based on a supplied name.
     * @param string $name Relation name
     * @return string
     */
    public function getRelationType($name)
    {
        foreach (static::$relationTypes as $type) {
            if (isset($this->{$type}[$name]))
                return $type;
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

        if ($relationType == 'morphTo' || !isset($relation[0]))
            return null;

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
        if (!array_key_exists('push', $definition))
            return true;

        return (bool) $definition['push'];
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

        if (!isset($relation[0]) && $relationType != 'morphTo')
            throw new InvalidArgumentException(sprintf("Relation '%s' on model '%s' should have at least a classname.", $relationName, get_called_class()));

        if (isset($relation[0]) && $relationType == 'morphTo')
            throw new InvalidArgumentException(sprintf("Relation '%s' on model '%s' is a morphTo relation and should not contain additional arguments.", $relationName, get_called_class()));

        switch ($relationType) {
            case 'hasOne':
            case 'hasMany':
                $relation = $this->validateRelationArgs($relationName, ['primaryKey', 'localKey']);
                $relationObj = $this->$relationType($relation[0], $relation['primaryKey'], $relation['localKey'], $relationName);
                break;

            case 'belongsTo':
                $relation = $this->validateRelationArgs($relationName, ['foreignKey', 'parentKey']);
                $relationObj = $this->$relationType($relation[0], $relation['foreignKey'], $relation['parentKey'], $relationName);
                break;

            case 'belongsToMany':
                $relation = $this->validateRelationArgs($relationName, ['table', 'primaryKey', 'foreignKey', 'pivot', 'timestamps']);
                $relationObj = $this->$relationType($relation[0], $relation['table'], $relation['primaryKey'], $relation['foreignKey'], $relationName);
                break;

            case 'morphTo':
                $relation = $this->validateRelationArgs($relationName, ['name', 'type', 'id']);
                $relationObj = $this->$relationType($relation['name'], $relation['type'], $relation['id']);
                break;

            case 'morphOne':
            case 'morphMany':
                $relation = $this->validateRelationArgs($relationName, ['type', 'id', 'localKey'], ['name']);
                $relationObj = $this->$relationType($relation[0], $relation['name'], $relation['type'], $relation['id'], $relation['localKey'], $relationName);
                break;

            case 'morphToMany':
                $relation = $this->validateRelationArgs($relationName, ['table', 'primaryKey', 'foreignKey', 'pivot', 'timestamps'], ['name']);
                $relationObj = $this->$relationType($relation[0], $relation['name'], $relation['table'], $relation['primaryKey'], $relation['foreignKey'], false, $relationName);
                break;

            case 'morphedByMany':
                $relation = $this->validateRelationArgs($relationName, ['table', 'primaryKey', 'foreignKey', 'pivot', 'timestamps'], ['name']);
                $relationObj = $this->$relationType($relation[0], $relation['name'], $relation['table'], $relation['primaryKey'], $relation['foreignKey'], $relationName);
                break;

            case 'attachOne':
            case 'attachMany':
                $relation = $this->validateRelationArgs($relationName, ['public', 'localKey']);
                $relationObj = $this->$relationType($relation[0], $relation['public'], $relation['localKey'], $relationName);
                break;

            case 'hasManyThrough':
                $relation = $this->validateRelationArgs($relationName, ['primaryKey', 'throughKey'], ['through']);
                $relationObj = $this->$relationType($relation[0], $relation['through'], $relation['primaryKey'], $relation['throughKey']);
                break;

            default:
                throw new InvalidArgumentException(sprintf("There is no such relation type known as '%s' on model '%s'.", $relationType, get_called_class()));
        }

        return $this->applyRelationFilters($relation, $relationObj);
    }

    /**
     * Validate relation supplied arguments.
     */
    protected function validateRelationArgs($relationName, $optional, $required = [])
    {

        $relation = $this->getRelationDefinition($relationName);

        // Query filter arguments
        $filters = ['scope', 'conditions', 'order', 'pivot', 'timestamps', 'push'];

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

        if ($missingRequired)
            throw new InvalidArgumentException("Relation '".$relationName."' on model '".get_called_class().' should contain the following key(s): '.join(', ', $missingRequired));

        return $relation;
    }

    /**
     * Apply filters to relationship objects as supplied by arguments.
     * @param $args Captured relationship arguments
     * @param $relation Relationship object
     * @return Relationship object
     */
    protected function applyRelationFilters($args, $relation)
    {
        /*
         * Pivot data (belongsToMany, morphToMany, morphByMany)
         */
        if ($pivotData = $args['pivot']) {
            $relation->withPivot($pivotData);
        }

        /*
         * Pivot timestamps (belongsToMany, morphToMany, morphByMany)
         */
        if ($args['timestamps']) {
            $relation->withTimestamps();
        }

        /*
         * Conditions
         */
        if ($conditions = $args['conditions']) {
            $relation->whereRaw($conditions);
        }

        /*
         * Sort order
         */
        if ($orderBy = $args['order']) {
            if (!is_array($orderBy))
                $orderBy = [$orderBy];

            foreach ($orderBy as $order) {
                $column = $order;
                $direction = 'asc';

                $parts = explode(' ', $order);
                if (count($parts) > 1)
                    list($column, $direction) = $parts;

                $relation->orderBy($column, $direction);
            }
        }

        /*
         * Scope
         */
        if ($scope = $args['scope']) {
            $relation->$scope();
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
        if (is_null($name))
            $name = snake_case($this->getRelationCaller());

        list($type, $id) = $this->getMorphs($name, $type, $id);
        $class = $this->$type;

        return $this->belongsTo($class, $id);
    }

    /**
     * Define a one-to-one relationship.
     * This code is a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\HasOne
     */
    public function hasOne($related, $primaryKey = null, $localKey = null, $relationName = null)
    {
        if (is_null($relationName))
            $relationName = $this->getRelationCaller();

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
        if (is_null($relationName))
            $relationName = $this->getRelationCaller();

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
        if (is_null($relationName))
            $relationName = $this->getRelationCaller();

        if (is_null($foreignKey))
            $foreignKey = snake_case($relationName).'_id';

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
        if (is_null($relationName))
            $relationName = $this->getRelationCaller();

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
    public function hasManyThrough($related, $through, $primaryKey = null, $throughKey = null, $relationName = null)
    {
        if (is_null($relationName))
            $relationName = $this->getRelationCaller();

        $instance = new $related;
        $throughInstance = new $through;
        $primaryKey = $primaryKey ?: $this->getForeignKey();
        $throughKey = $throughKey ?: $throughInstance->getForeignKey();

        return new HasManyThrough($instance->newQuery(), $this, $throughInstance, $primaryKey, $throughKey);
    }

    /**
     * Define a polymorphic one-to-many relationship.
     * This code is a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\MorphMany
     */
    public function morphMany($related, $name, $type = null, $id = null, $localKey = null, $relationName = null)
    {
        if (is_null($relationName))
            $relationName = $this->getRelationCaller();

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
        if (is_null($relationName))
            $relationName = $this->getRelationCaller();

        $primaryKey = $primaryKey ?: $this->getForeignKey();
        $instance = new $related;
        $foreignKey = $foreignKey ?: $instance->getForeignKey();

        if (is_null($table))
            $table = $this->joiningTable($related);

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
        if (is_null($relationName))
            $relationName = $this->getRelationCaller();

        $primaryKey = $primaryKey ?: $name.'_id';
        $instance = new $related;
        $foreignKey = $foreignKey ?: $instance->getForeignKey();

        if (is_null($table))
            $table = $this->joiningTable($related);

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
        if (is_null($relationName))
            $relationName = $this->getRelationCaller();

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
        if (is_null($relationName))
            $relationName = $this->getRelationCaller();

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
        if (is_null($relationName))
            $relationName = $this->getRelationCaller();

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
        $relationType = $this->getRelationType($relationName);
        $relationObj = $this->$relationName();
        $value = null;

        switch ($relationType) {
            case 'belongsTo':
                $value = $this->getAttribute($relationObj->getForeignKey());
                break;

            case 'belongsToMany':
            case 'morphToMany':
            case 'morphedByMany':
                $value = $relationObj->getRelatedIds();
                break;
        }

        return $value;
    }

    /**
     * Sets a relation value directly from its attribute.
     */
    protected function setRelationValue($relationName, $value)
    {
        $relationType = $this->getRelationType($relationName);
        $relationObj = $this->$relationName();

        switch ($relationType) {

            case 'belongsToMany':
            case 'morphToMany':
            case 'morphedByMany':
                // Nulling the relationship
                if (!$value) {
                    if ($this->exists) $relationObj->detach();
                    break;
                }

                if (!is_array($value)) $value = [$value];

                // Do not sync until the model is saved
                $this->bindEventOnce('model.afterSave', function() use ($relationObj, $value){
                    $relationObj->sync($value);
                });
                break;

            case 'belongsTo':
                // Nulling the relationship
                if (!$value) {
                    $this->setAttribute($relationObj->getForeignKey(), null);
                    break;
                }

                if ($value instanceof EloquentModel) {
                    /*
                     * Non existent model, use a single serve event to associate it again when ready
                     */
                    if (!$value->exists) {
                        $value->bindEventOnce('model.afterSave', function() use ($relationObj, $value){
                            $relationObj->associate($value);
                        });
                    }

                    $relationObj->associate($value);
                }
                else
                    $this->setAttribute($relationObj->getForeignKey(), $value);
                break;

            case 'attachMany':
                if ($value instanceof UploadedFile) {
                    $this->bindEventOnce('model.afterSave', function() use ($relationObj, $value){
                        $relationObj->create(['data' => $value]);
                    });
                }
                elseif (is_array($value)) {
                    $files = [];
                    foreach ($value as $_value) {
                        if ($_value instanceof UploadedFile)
                            $files[] = $_value;
                    }
                    $this->bindEventOnce('model.afterSave', function() use ($relationObj, $files){
                        foreach ($files as $file) {
                            $relationObj->create(['data' => $file]);
                        }
                    });
                }
                break;

            case 'attachOne':
                if (is_array($value))
                    $value = reset($value);

                if ($value instanceof UploadedFile) {
                    $this->bindEventOnce('model.afterSave', function() use ($relationObj, $value){
                        $relationObj->create(['data' => $value]);
                    });
                }
                break;
        }
    }

    //
    // Saving
    //

    /**
     * Save the model to the database. Is used by {@link save()} and {@link forceSave()}.
     * @return bool
     */
    protected function saveInternal($data = [], $options = [])
    {
        if ($data !== null)
            $this->fill($data);

        // Event
        if ($this->fireEvent('model.saveInternal', [$data, $options], true) === false)
            return false;

        /*
         * Validate attributes before trying to save
         */
        foreach ($this->attributes as $attribute => $value) {
            if (is_array($value))
                throw new Exception(sprintf('Unexpected type of array, should attribute "%s" be jsonable?', $attribute));
        }

        // Save the record
        $result = parent::save($options);

        // Halted by event
        if ($result === false)
            return $result;

        /*
         * If there is nothing to update, Eloquent will not fire afterSave(),
         * events should still fire for consistency.
         */
        if ($result === null) {
            $this->fireModelEvent('updated', false);
            $this->fireModelEvent('saved', false);
        }

        // Apply any deferred bindings
        if ($this->sessionKey !== null)
            $this->commitDeferred($this->sessionKey);

        return $result;
    }

    /**
     * Save the model to the database.
     * @return bool
     */
    public function save(array $data = null, $sessionKey = null)
    {
        $this->sessionKey = $sessionKey;
        return $this->saveInternal($data, ['force' => false]);
    }

    /**
     * Save the model and all of its relationships.
     * @return bool
     */
    public function push($sessionKey = null, $options = [])
    {
        $always = array_get($options, 'always', false);

        if (!$this->save(null, $sessionKey) && !$always)
            return false;

        foreach ($this->relations as $name => $models) {
            if (!$this->isRelationPushable($name))
                continue;

            foreach (Collection::make($models) as $model) {
                if (!$model->push($sessionKey))
                    return false;
            }
        }

        return true;
    }

    /**
     * Pushes the first level of relations even if the parent
     * model has no changes.
     * @return bool
     */
    public function alwaysPush($sessionKey)
    {
        return $this->push($sessionKey, ['always' => true]);
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
        // Before Event
        if (($attr = $this->fireEvent('model.beforeGetAttribute', [$key], true)) !== null)
            return $attr;

        $attr = parent::getAttribute($key);

        if ($attr === null) {
            if ($this->hasRelation($key)) {
                $this->relations[$key] = $this->$key()->getResults();
                return $this->relations[$key];
            }
        }

        // After Event
        if (($_attr = $this->fireEvent('model.getAttribute', [$key, $attr], true)) !== null)
            return $_attr;

        return $attr;
    }

    /**
     * Get a plain attribute (not a relationship).
     * @param  string  $key
     * @return mixed
     */
    protected function getAttributeValue($key)
    {
        $attr = parent::getAttributeValue($key);

        // Handle jsonable
        if (in_array($key, $this->jsonable) && !empty($attr)) {
            if ($value = json_decode($attr, true))
                $attr = $value;
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
        return $this->methodExists('get'.studly_case($key).'Attribute');
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
        // Before Event
        if (($_value = $this->fireEvent('model.beforeSetAttribute', [$key, $value], true)) !== null)
            $value = $_value;

        // Handle jsonable
        if (in_array($key, $this->jsonable) && (!empty($value) || is_array($value))) {
            $value = json_encode($value);
        }

        // Handle direct relation setting
        if ($this->hasRelation($key)) {
            $result = $this->setRelationValue($key, $value);
        }
        else {
            if (!is_object($value) && !is_array($value) && !is_null($value) && !is_bool($value))
                $value = trim($value);

            $result = parent::setAttribute($key, $value);
        }

        // After Event
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
        return $this->methodExists('set'.studly_case($key).'Attribute');
    }

}
