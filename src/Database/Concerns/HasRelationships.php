<?php namespace October\Rain\Database\Concerns;

use October\Rain\Support\Arr;
use October\Rain\Support\Str;
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
use October\Rain\Database\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as CollectionBase;
use InvalidArgumentException;

/**
 * HasRelationships is a concern used by the \October\Rain\Database\Model class, employing a
 * cleaner declaration of model relationships.
 *
 * The relation definitions uses an almost identical approach to the relation methods defined
 * by Eloquent, instead using class properties to make the class file less cluttered and keep
 * the logic separated from the definition.
 *
 * Relations should be declared with keys as the relation name and value as a mixed array.
 * The relation type `$morphTo` does not include a class name as the first value.
 *
 * Example:
 *
 *     class Order extends Model
 *     {
 *         protected $hasMany = [
 *             'items' => Item::class
 *         ];
 *     }
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasRelationships
{
    /**
     * @var array hasOne related record, inverse of belongsTo.
     *
     *     protected $hasOne = [
     *         'owner' => [User::class, 'key' => 'user_id']
     *     ];
     *
     */
    public $hasOne = [];

    /**
     * @var array hasMany related records, inverse of belongsTo.
     *
     *     protected $hasMany = [
     *         'items' => Item::class
     *     ];
     */
    public $hasMany = [];

    /**
     * @var array belongsTo another record with a local key attribute
     *
     *     protected $belongsTo = [
     *         'parent' => [Category::class, 'key' => 'parent_id']
     *     ];
     */
    public $belongsTo = [];

    /**
     * @var array belongsToMany to multiple records using a join table.
     *
     *     protected $belongsToMany = [
     *         'groups' => [Group::class, 'table'=> 'join_groups_users']
     *     ];
     */
    public $belongsToMany = [];

    /**
     * @var array morphTo another record using local key and type attributes
     *
     *     protected $morphTo = [
     *         'pictures' => []
     *     ];
     */
    public $morphTo = [];

    /**
     * @var array morphOne related record, inverse of morphTo.
     *
     *     protected $morphOne = [
     *         'log' => [History::class, 'name' => 'user']
     *     ];
     */
    public $morphOne = [];

    /**
     * @var array morphMany related records, inverse of morphTo.
     *
     *     protected $morphMany = [
     *         'log' => [History::class, 'name' => 'user']
     *     ];
     */
    public $morphMany = [];

    /**
     * @var array morphToMany to multiple records using a join table.
     *
     *     protected $morphToMany = [
     *         'tag' => [Tag::class, 'table' => 'tagables', 'name' => 'tagable']
     *     ];
     */
    public $morphToMany = [];

    /**
     * @var array morphedByMany to a polymorphic, inverse many-to-many relationship.
     *
     *     public $morphedByMany = [
     *         'tag' => [Tag::class, 'table' => 'tagables', 'name' => 'tagable']
     *     ];
     */
    public $morphedByMany = [];

    /**
     * @var array attachOne file attachment.
     *
     *     protected $attachOne = [
     *         'picture' => [\October\Rain\Database\Attach\File::class, 'public' => false]
     *     ];
     */
    public $attachOne = [];

    /**
     * @var array attachMany file attachments.
     *
     *     protected $attachMany = [
     *         'pictures' => [\October\Rain\Database\Attach\File::class, 'name'=> 'imageable']
     *     ];
     */
    public $attachMany = [];

    /**
     * @var array hasManyThrough is related records through another record.
     *
     *     protected $hasManyThrough = [
     *         'posts' => [Post::class, 'through' => User::class]
     *     ];
     */
    public $hasManyThrough = [];

    /**
     * @var array hasOneThrough is a related record through another record.
     *
     *     protected $hasOneThrough = [
     *         'post' => [Post::class, 'through' => User::class]
     *     ];
     */
    public $hasOneThrough = [];

    /**
     * @var array relationTypes expected, used to cycle and verify relationships.
     */
    protected static $relationTypes = [
        'hasOne',
        'hasMany',
        'belongsTo',
        'belongsToMany',
        'morphTo',
        'morphOne',
        'morphMany',
        'morphToMany',
        'morphedByMany',
        'attachOne',
        'attachMany',
        'hasOneThrough',
        'hasManyThrough'
    ];

    //
    // Relations
    //

    /**
     * hasRelation checks if model has a relationship by supplied name
     */
    public function hasRelation(string $name): bool
    {
        return $this->getRelationType($name) !== null;
    }

    /**
     * getRelationDefinition returns relationship details from a supplied name
     */
    public function getRelationDefinition(string $name): array
    {
        if (($type = $this->getRelationType($name)) !== null) {
            return (array) $this->{$type}[$name] + $this->getRelationDefaults($type);
        }

        return [];
    }

    /**
     * getRelationDefinitions returns relationship details for all relations
     * defined on this model
     * @return array
     */
    public function getRelationDefinitions()
    {
        $result = [];

        foreach (static::$relationTypes as $type) {
            $result[$type] = $this->{$type};

            // Apply default values for the relation type
            if ($defaults = $this->getRelationDefaults($type)) {
                foreach ($result[$type] as $relation => $options) {
                    $result[$type][$relation] = (array) $options + $defaults;
                }
            }
        }

        return $result;
    }

    /**
     * getRelationType returns a relationship type based on a supplied name
     * @param string $name Relation name
     * @return \October\Rain\Database\Relation
     */
    public function getRelationType($name)
    {
        foreach (static::$relationTypes as $type) {
            if (isset($this->{$type}[$name])) {
                return $type;
            }
        }

        return null;
    }

    /**
     * isRelationTypeSingular returns true if the relation is expected to return
     * a single record versus a collection of records.
     */
    public function isRelationTypeSingular($name): bool
    {
        return in_array($this->getRelationType($name), [
            'hasOne',
            'belongsTo',
            'morphTo',
            'morphOne',
            'attachOne',
            'hasOneThrough'
        ]);
    }

    /**
     * makeRelation returns a relation class object, supporting nested relations with
     * dot notation
     * @param string $name
     * @return \Model|null
     */
    public function makeRelation($name)
    {
        if (str_contains($name, '.')) {
            $model = $this;
            $parts = explode('.', $name);
            while ($relationName = array_shift($parts)) {
                if (!$model = $model->makeRelation($relationName)) {
                    return null;
                }
            }
            return $model;
        }

        $relation = $this->getRelationDefinition($name);
        $relationType = $this->getRelationType($name);

        if ($relationType === 'morphTo' || !isset($relation[0])) {
            return null;
        }

        return $this->makeRelationInternal($name, $relation[0]);
    }

    /**
     * makeRelationInternal is used internally to create a new related instance. It also
     * fires the `afterRelation` to extend the created instance.
     */
    protected function makeRelationInternal(string $relationName, string $relationClass)
    {
        $model = $this->newRelatedInstance($relationClass);

        $this->fireEvent('model.afterRelation', [$relationName, $model]);
        $this->afterRelation($relationName, $model);

        return $model;
    }

    /**
     * isRelationPushable determines whether the specified relation should be saved
     * when `push()` is called instead of `save()` on the model. Defaults to `true`.
     */
    public function isRelationPushable(string $name): bool
    {
        $definition = $this->getRelationDefinition($name);

        if (!array_key_exists('push', $definition)) {
            return true;
        }

        return (bool) $definition['push'];
    }

    /**
     * getRelationDefaults returns default relation arguments for a given type.
     * @param string $type
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
     * handleRelation looks for the relation and does the correct magic as Eloquent would require
     * inside relation methods. For more information, read the documentation of the mentioned property.
     * @param string $relationName the relation key, camel-case version
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    protected function handleRelation($relationName)
    {
        $relationType = $this->getRelationType($relationName);
        $relation = $this->getRelationDefinition($relationName);

        if (!isset($relation[0]) && $relationType !== 'morphTo') {
            throw new InvalidArgumentException(sprintf(
                "Relation '%s' on model '%s' should have at least a classname.",
                $relationName,
                static::class
            ));
        }

        if (isset($relation[0]) && $relationType === 'morphTo') {
            throw new InvalidArgumentException(sprintf(
                "Relation '%s' on model '%s' is a morphTo relation and should not contain additional arguments.",
                $relationName,
                static::class
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
                $relation = $this->validateRelationArgs($relationName, ['table', 'key', 'otherKey', 'parentKey', 'relatedKey', 'pivot', 'timestamps']);
                $relationObj = $this->$relationType($relation[0], $relation['table'], $relation['key'], $relation['otherKey'], $relation['parentKey'], $relation['relatedKey'], $relationName);
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
                $relation = $this->validateRelationArgs($relationName, ['table', 'key', 'otherKey', 'parentKey', 'relatedKey', 'pivot', 'timestamps'], ['name']);
                $relationObj = $this->$relationType($relation[0], $relation['name'], $relation['table'], $relation['key'], $relation['otherKey'], $relation['parentKey'], $relation['relatedKey'], false, $relationName);
                break;

            case 'morphedByMany':
                $relation = $this->validateRelationArgs($relationName, ['table', 'key', 'otherKey', 'parentKey', 'relatedKey', 'pivot', 'timestamps'], ['name']);
                $relationObj = $this->$relationType($relation[0], $relation['name'], $relation['table'], $relation['key'], $relation['otherKey'], $relation['parentKey'], $relation['relatedKey'], $relationName);
                break;

            case 'attachOne':
            case 'attachMany':
                $relation = $this->validateRelationArgs($relationName, ['public', 'key']);
                $relationObj = $this->$relationType($relation[0], $relation['public'], $relation['key'], $relationName);
                break;

            case 'hasOneThrough':
            case 'hasManyThrough':
                $relation = $this->validateRelationArgs($relationName, ['key', 'throughKey', 'otherKey', 'secondOtherKey'], ['through']);
                $relationObj = $this->$relationType($relation[0], $relation['through'], $relation['key'], $relation['throughKey'], $relation['otherKey'], $relation['secondOtherKey'], $relationName);
                break;

            default:
                throw new InvalidArgumentException(sprintf("There is no such relation type known as '%s' on model '%s'.", $relationType, static::class));
        }

        // Relation hook event
        $this->fireEvent('model.beforeRelation', [$relationName, $relationObj]);
        $this->beforeRelation($relationName, $relationObj);

        return $relationObj;
    }

    /**
     * validateRelationArgs supplied relation arguments
     */
    protected function validateRelationArgs($relationName, $optional, $required = [])
    {
        $relation = $this->getRelationDefinition($relationName);

        // Query filter arguments
        $filters = ['scope', 'conditions', 'order', 'pivot', 'timestamps', 'push', 'count', 'default'];

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
            throw new InvalidArgumentException(sprintf(
                'Relation "%s" on model "%s" should contain the following key(s): %s',
                $relationName,
                static::class,
                implode(', ', $missingRequired)
            ));
        }

        return $relation;
    }

    /**
     * getRelationCustomClass returns a custom relation class name for
     * the relation or null if none is found.
     */
    protected function getRelationCustomClass(string $name): ?string
    {
        if (($type = $this->getRelationType($name)) !== null) {
            return $this->{$type}[$name]['relationClass'] ?? null;
        }

        return null;
    }

    /**
     * hasOne defines a one-to-one relationship.
     * This code is a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\HasOne
     */
    public function hasOne($related, $primaryKey = null, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = $this->makeRelationInternal($relationName, $related);

        $primaryKey = $primaryKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        $relationClass = $this->getRelationCustomClass($relationName) ?: HasOne::class;

        return new $relationClass($instance->newQuery(), $this, $instance->getTable().'.'.$primaryKey, $localKey, $relationName);
    }

    /**
     * morphOne defines a polymorphic one-to-one relationship.
     * This code is a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\MorphOne
     */
    public function morphOne($related, $name, $type = null, $id = null, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = $this->makeRelationInternal($relationName, $related);

        [$type, $id] = $this->getMorphs($name, $type, $id);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        $relationClass = $this->getRelationCustomClass($relationName) ?: MorphOne::class;

        return new $relationClass($instance->newQuery(), $this, $table.'.'.$type, $table.'.'.$id, $localKey, $relationName);
    }

    /**
     * belongsTo defines an inverse one-to-one or many relationship. Overridden from
     * \Eloquent\Model to allow the usage of the intermediary methods to handle the
     * relationsData array.
     * @return \October\Rain\Database\Relations\BelongsTo
     */
    public function belongsTo($related, $foreignKey = null, $parentKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = $this->makeRelationInternal($relationName, $related);

        if (is_null($foreignKey)) {
            $foreignKey = snake_case($relationName).'_id';
        }

        $parentKey = $parentKey ?: $instance->getKeyName();

        $relationClass = $this->getRelationCustomClass($relationName) ?: BelongsTo::class;

        return new $relationClass($instance->newQuery(), $this, $foreignKey, $parentKey, $relationName);
    }

    /**
     * morphTo defines a polymorphic, inverse one-to-one or many relationship.
     * Overridden from \Eloquent\Model to allow the usage of the intermediary
     * methods to handle the relation.
     * @return \October\Rain\Database\Relations\BelongsTo
     */
    public function morphTo($name = null, $type = null, $id = null, $ownerKey = null)
    {
        if (is_null($name)) {
            $name = $this->getRelationCaller();
        }

        [$type, $id] = $this->getMorphs(Str::snake($name), $type, $id);

        return empty($class = $this->{$type})
                    ? $this->morphEagerTo($name, $type, $id, $ownerKey)
                    : $this->morphInstanceTo($class, $name, $type, $id, $ownerKey);
    }

    /**
     * morphEagerTo defines a polymorphic, inverse one-to-one or many relationship.
     * @param  string  $name
     * @param  string  $type
     * @param  string  $id
     * @param  string  $ownerKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    protected function morphEagerTo($name, $type, $id, $ownerKey)
    {
        return new MorphTo(
            $this->newQuery()->setEagerLoads([]),
            $this,
            $id,
            $ownerKey,
            $type,
            $name
        );
    }

    /**
     * morphInstanceTo defines a polymorphic, inverse one-to-one or many relationship
     * @param  string  $target
     * @param  string  $name
     * @param  string  $type
     * @param  string  $id
     * @param  string  $ownerKey
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    protected function morphInstanceTo($target, $name, $type, $id, $ownerKey)
    {
        $instance = $this->newRelatedInstance(
            static::getActualClassNameForMorph($target)
        );

        return new MorphTo(
            $instance->newQuery(),
            $this,
            $id,
            $ownerKey ?? $instance->getKeyName(),
            $type,
            $name
        );
    }

    /**
     * hasMany defines a one-to-many relationship.
     * This code is a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\HasMany
     */
    public function hasMany($related, $primaryKey = null, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = $this->makeRelationInternal($relationName, $related);

        $primaryKey = $primaryKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        $relationClass = $this->getRelationCustomClass($relationName) ?: HasMany::class;

        return new $relationClass($instance->newQuery(), $this, $instance->getTable().'.'.$primaryKey, $localKey, $relationName);
    }

    /**
     * hasManyThrough defines a has-many-through relationship.
     * This code is a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\HasManyThrough
     */
    public function hasManyThrough($related, $through, $primaryKey = null, $throughKey = null, $localKey = null, $secondLocalKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $throughInstance = new $through;

        $primaryKey = $primaryKey ?: $this->getForeignKey();

        $throughKey = $throughKey ?: $throughInstance->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        $secondLocalKey = $secondLocalKey ?: $throughInstance->getKeyName();

        $instance = $this->makeRelationInternal($relationName, $related);

        $relationClass = $this->getRelationCustomClass($relationName) ?: HasManyThrough::class;

        return new $relationClass($instance->newQuery(), $this, $throughInstance, $primaryKey, $throughKey, $localKey, $secondLocalKey, $relationName);
    }

    /**
     * hasOneThrough define a has-one-through relationship.
     * This code is a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\HasOneThrough
     */
    public function hasOneThrough($related, $through, $primaryKey = null, $throughKey = null, $localKey = null, $secondLocalKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $throughInstance = new $through;

        $primaryKey = $primaryKey ?: $this->getForeignKey();

        $throughKey = $throughKey ?: $throughInstance->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        $secondLocalKey = $secondLocalKey ?: $throughInstance->getKeyName();

        $instance = $this->makeRelationInternal($relationName, $related);

        $relationClass = $this->getRelationCustomClass($relationName) ?: HasOneThrough::class;

        return new $relationClass($instance->newQuery(), $this, $throughInstance, $primaryKey, $throughKey, $localKey, $secondLocalKey, $relationName);
    }

    /**
     * morphMany defines a polymorphic one-to-many relationship.
     * This code is a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\MorphMany
     */
    public function morphMany($related, $name, $type = null, $id = null, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = $this->makeRelationInternal($relationName, $related);

        [$type, $id] = $this->getMorphs($name, $type, $id);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        $relationClass = $this->getRelationCustomClass($relationName) ?: MorphMany::class;

        return new $relationClass($instance->newQuery(), $this, $table.'.'.$type, $table.'.'.$id, $localKey, $relationName);
    }

    /**
     * belongsToMany defines a many-to-many relationship.
     * This code is almost a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\BelongsToMany
     */
    public function belongsToMany($related, $table = null, $primaryKey = null, $foreignKey = null, $parentKey = null, $relatedKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = $this->makeRelationInternal($relationName, $related);

        $primaryKey = $primaryKey ?: $this->getForeignKey();

        $foreignKey = $foreignKey ?: $instance->getForeignKey();

        if (is_null($table)) {
            $table = $this->joiningTable($related);
        }

        $relationClass = $this->getRelationCustomClass($relationName) ?: BelongsToMany::class;

        return new $relationClass(
            $instance->newQuery(),
            $this,
            $table,
            $primaryKey,
            $foreignKey,
            $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(),
            $relationName
        );
    }

    /**
     * morphToMany defines a polymorphic many-to-many relationship.
     * This code is almost a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\MorphToMany
     */
    public function morphToMany($related, $name, $table = null, $primaryKey = null, $foreignKey = null, $parentKey = null, $relatedKey = null, $inverse = false, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = $this->makeRelationInternal($relationName, $related);

        $primaryKey = $primaryKey ?: $name.'_id';

        $foreignKey = $foreignKey ?: $instance->getForeignKey();

        $table = $table ?: Str::plural($name);

        $relationClass = $this->getRelationCustomClass($relationName) ?: MorphToMany::class;

        return new $relationClass(
            $instance->newQuery(),
            $this,
            $name,
            $table,
            $primaryKey,
            $foreignKey,
            $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(),
            $relationName,
            $inverse
        );
    }

    /**
     * morphedByMany defines a polymorphic many-to-many inverse relationship.
     * This code is almost a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\MorphToMany
     */
    public function morphedByMany($related, $name, $table = null, $primaryKey = null, $foreignKey = null, $parentKey = null, $relatedKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $primaryKey = $primaryKey ?: $this->getForeignKey();

        $foreignKey = $foreignKey ?: $name.'_id';

        return $this->morphToMany(
            $related,
            $name,
            $table,
            $primaryKey,
            $foreignKey,
            $parentKey,
            $relatedKey,
            true,
            $relationName
        );
    }

    /**
     * attachOne defines an attachment one-to-one relationship.
     * This code is a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\MorphOne
     */
    public function attachOne($related, $isPublic = true, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = $this->makeRelationInternal($relationName, $related);

        [$type, $id] = $this->getMorphs('attachment', null, null);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        $relationClass = $this->getRelationCustomClass($relationName) ?: AttachOne::class;

        return new $relationClass($instance->newQuery(), $this, $table.'.'.$type, $table.'.'.$id, $isPublic, $localKey, $relationName);
    }

    /**
     * attachMany defines an attachment one-to-many relationship.
     * This code is a duplicate of Eloquent but uses a Rain relation class.
     * @return \October\Rain\Database\Relations\MorphMany
     */
    public function attachMany($related, $isPublic = null, $localKey = null, $relationName = null)
    {
        if (is_null($relationName)) {
            $relationName = $this->getRelationCaller();
        }

        $instance = $this->makeRelationInternal($relationName, $related);

        [$type, $id] = $this->getMorphs('attachment', null, null);

        $table = $instance->getTable();

        $localKey = $localKey ?: $this->getKeyName();

        $relationClass = $this->getRelationCustomClass($relationName) ?: AttachMany::class;

        return new $relationClass($instance->newQuery(), $this, $table.'.'.$type, $table.'.'.$id, $isPublic, $localKey, $relationName);
    }

    /**
     * getRelationCaller finds the calling function name from the stack trace.
     */
    protected function getRelationCaller()
    {
        $backtrace = debug_backtrace(false);

        $caller = $backtrace[2]['function'] === 'handleRelation'
            ? $backtrace[4]
            : $backtrace[2];

        return $caller['function'];
    }

    /**
     * getRelationSimpleValue returns a relation key value(s), not as an object.
     */
    public function getRelationSimpleValue($relationName)
    {
        return $this->$relationName()->getSimpleValue();
    }

    /**
     * setRelationSimpleValue sets a relation value directly from its attribute.
     */
    protected function setRelationSimpleValue($relationName, $value)
    {
        $this->$relationName()->setSimpleValue($value);
    }

    /**
     * performDeleteOnRelations locates relations with delete flag and cascades the
     * delete event. This is called before the parent model is deleted. This method
     * checks in with the Multisite trait to preserve shared relations.
     *
     * @see \October\Rain\Database\Traits\Multisite::canDeleteMultisiteRelation
     */
    protected function performDeleteOnRelations()
    {
        $definitions = $this->getRelationDefinitions();
        $useMultisite = $this->isClassInstanceOf(\October\Contracts\Database\MultisiteInterface::class) && $this->isMultisiteEnabled();

        foreach ($definitions as $type => $relations) {
            foreach ($relations as $name => $options) {
                // Detect and preserve shared multisite relationships
                if ($useMultisite && !$this->canDeleteMultisiteRelation($name, $type)) {
                    continue;
                }

                // Belongs-To-Many should clean up after itself by default
                if ($type === 'belongsToMany') {
                    if (!Arr::get($options, 'detach', true)) {
                        return;
                    }

                    $this->{$name}()->detach();
                }
                // Hard 'delete' definition
                else {
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
            }
        }
    }
}
