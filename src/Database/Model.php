<?php namespace October\Rain\Database;

use October\Rain\Support\Arr;
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
    use Concerns\HasReplication;
    use Concerns\HasAttributes;
    use Concerns\HasRelationships;
    use Concerns\HasJsonable;
    use Concerns\HasEvents;
    use \October\Rain\Support\Traits\Emitter;
    use \October\Rain\Extension\ExtendableTrait;
    use \October\Rain\Database\Traits\DeferredBinding;

    /**
     * @var array implement behaviors for this model.
     */
    public $implement;

    /**
     * @var array Make the model's attributes public so behaviors can modify them.
     */
    public $attributes = [];

    /**
     * @var array List of datetime attributes to convert to an instance of Carbon/DateTime objects.
     */
    protected $dates = [];

    /**
     * @var bool trimStrings will trim all string attributes of whitespace
     */
    public $trimStrings = true;

    /**
     * __construct
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct();

        $this->bootNicerEvents();

        $this->extendableConstruct();

        $this->fill($attributes);
    }

    /**
     * make a new model and return the instance
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public static function make($attributes = [])
    {
        return new static($attributes);
    }

    /**
     * create a new model and return the instance.
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
     * reload the model attributes from the database.
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
     * reloadRelations for this model.
     * @param string  $relationName
     * @return void
     */
    public function reloadRelations($relationName = null)
    {
        if (!$relationName) {
            $this->setRelations([]);
        }
        else {
            unset($this->relations[$relationName]);
        }
    }

    /**
     * extend this object properties upon construction.
     */
    public static function extend(callable $callback)
    {
        self::extendableExtendCallback($callback);
    }

    /**
     * newInstance creates a new instance of the given model.
     * @param  array  $attributes
     * @param  bool  $exists
     * @return static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $model = parent::newInstance($attributes, $exists);

        /**
         * @event model.newInstance
         * Called when a new instance of a model is created
         *
         * Example usage:
         *
         *     $model->bindEvent('model.newInstance', function (\October\Rain\Database\Model $newModel) use (\October\Rain\Database\Model $model) {
         *         // Transfer custom properties
         *         $newModel->isLocked = $model->isLocked;
         *     });
         *
         */
        $this->fireEvent('model.newInstance', [$model]);

        return $model;
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

    //
    // Overrides
    //

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
                $value->format('Y-m-d H:i:s.u'),
                $value->getTimezone()
            );
        }

        if (is_numeric($value)) {
            return Argon::createFromTimestamp($value);
        }

        if ($this->isStandardDateFormat($value)) {
            return Argon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        return Argon::createFromFormat(
            str_replace('.v', '.u', $this->getDateFormat()),
            $value
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
            : Pivot::fromAttributes($parent, $attributes, $table, $exists);
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

        if (!array_key_exists('pivotModel', $definition)) {
            return;
        }

        return $this->newPivot($parent, $attributes, $table, $exists, $definition['pivotModel']);
    }

    //
    // Saving
    //

    /**
     * saveInternal is an internal method that saves the model to the database.
     * This is used by {@link save()} and {@link forceSave()}.
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

        return $this->saveInternal((array) $options);
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
    public function alwaysPush($options, $sessionKey)
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
                    $relation->each(function ($model) {
                        $model->forceDelete();
                    });
                }
            }

            /*
             * Belongs-To-Many should clean up after itself by default
             */
            if ($type === 'belongsToMany') {
                foreach ($relations as $name => $options) {
                    if (!Arr::get($options, 'detach', true)) {
                        return;
                    }

                    $this->{$name}()->detach();
                }
            }
        }
    }
}
