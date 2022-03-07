<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as CollectionBase;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as BelongsToManyBase;

/**
 * BelongsToMany
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class BelongsToMany extends BelongsToManyBase
{
    use DeferOneOrMany;
    use DefinedConstraints;
    use \October\Rain\Database\Concerns\HasNicerPagination;

    /**
     * @var bool countMode sets this relation object is a 'count' helper
     * @deprecated use Laravel withCount() method instead
     */
    public $countMode = false;

    /**
     * @var bool orphanMode used when a join is not used, don't select aliased columns
     */
    public $orphanMode = false;

    /**
     * __construct a new belongs to many relationship instance.
     *
     * @param  string  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $relationName
     */
    public function __construct(
        Builder $query,
        Model $parent,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $relationName = null
    ) {
        parent::__construct(
            $query,
            $parent,
            $table,
            $foreignPivotKey,
            $relatedPivotKey,
            $parentKey,
            $relatedKey,
            $relationName
        );

        $this->addDefinedConstraints();
    }

    /**
     * shouldSelect gets the select columns for the relation query
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function shouldSelect(array $columns = ['*'])
    {
        if ($this->countMode) {
            return $this->table.'.'.$this->foreignPivotKey.' as pivot_'.$this->foreignPivotKey;
        }

        if ($columns === ['*']) {
            $columns = [$this->related->getTable().'.*'];
        }

        if ($this->orphanMode) {
            return $columns;
        }

        return array_merge($columns, $this->aliasedPivotColumns());
    }

    /**
     * save the supplied related model with deferred binding support.
     */
    public function save(Model $model, array $pivotData = [], $sessionKey = null)
    {
        $model->save();

        $this->add($model, $sessionKey, $pivotData);

        return $model;
    }

    /**
     * create a new instance of this related model with deferred binding support.
     */
    public function create(array $attributes = [], array $pivotData = [], $sessionKey = null)
    {
        $model = $this->related->create($attributes);

        $this->add($model, $sessionKey, $pivotData);

        return $model;
    }

    /**
     * attach overrides attach() method of BelongToMany relation
     * This is necessary in order to fire 'model.relation.beforeAttach', 'model.relation.attach' events
     * @param mixed $ids
     * @param array $attributes
     * @param bool  $touch
     */
    public function attach($ids, array $attributes = [], $touch = true)
    {
        // Normalize identifiers for events, this occurs internally in the parent logic
        // and should have no cascading effects.
        $parsedIds = $this->parseIds($ids);

        /**
         * @event model.relation.beforeAttach
         * Called before creating a new relation between models (only for BelongsToMany relation)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.beforeAttach', function (string $relationName, array $ids, array $attributes) use (\October\Rain\Database\Model $model) {
         *         foreach ($ids as $id) {
         *             if (!$model->isRelationValid($id)) {
         *                 return false;
         *             }
         *         }
         *     });
         *
         */
        if ($this->parent->fireEvent('model.relation.beforeAttach', [$this->relationName, &$parsedIds, &$attributes], true) === false) {
            return;
        }

        /*
         * See \Illuminate\Database\Eloquent\Relations\Concerns\InteractsWithPivotTable
         */
        parent::attach($parsedIds, $attributes, $touch);

        /**
         * @event model.relation.attach
         * Called after creating a new relation between models (only for BelongsToMany relation)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.attach', function (string $relationName, array $ids, array $attributes) use (\October\Rain\Database\Model $model) {
         *         foreach ($ids as $id) {
         *             traceLog("New relation {$relationName} was created", $id);
         *         }
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.attach', [$this->relationName, $parsedIds, $attributes]);
    }

    /**
     * detach overrides detach() method of BelongToMany relation.
     * This is necessary in order to fire 'model.relation.beforeDetach', 'model.relation.detach' events
     * @param mixed $ids
     * @param bool $touch
     * @return int|void
     */
    public function detach($ids = null, $touch = true)
    {
        // Normalize identifiers for events, this occurs internally in the parent logic
        // and should have no cascading effects. Null is used to detach everything.
        $parsedIds = $ids !== null ? $this->parseIds($ids) : $ids;

        /**
         * @event model.relation.beforeDetach
         * Called before removing a relation between models (only for BelongsToMany relation)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.beforeDetach', function (string $relationName, ?array $ids) use (\October\Rain\Database\Model $model) {
         *         foreach ($ids as $id) {
         *             if (!$model->isRelationValid($ids)) {
         *                 return false;
         *             }
         *         }
         *     });
         *
         */
        if ($this->parent->fireEvent('model.relation.beforeDetach', [$this->relationName, &$parsedIds], true) === false) {
            return;
        }

        /*
         * See \Illuminate\Database\Eloquent\Relations\Concerns\InteractsWithPivotTable
         */
        $result = parent::detach($parsedIds, $touch);

        /**
         * @event model.relation.detach
         * Called after removing a relation between models (only for BelongsToMany relation)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.detach', function (string $relationName, ?array $ids) use (\October\Rain\Database\Model $model) {
         *         foreach ($ids as $id) {
         *             traceLog("Relation {$relationName} was removed", $ids);
         *         }
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.detach', [$this->relationName, $parsedIds, $result]);
    }

    /**
     * add a model to this relationship type.
     */
    public function add(Model $model, $sessionKey = null, $pivotData = [])
    {
        if (is_array($sessionKey)) {
            $pivotData = $sessionKey;
            $sessionKey = null;
        }

        // Associate the model
        if ($sessionKey === null) {
            if ($this->parent->exists) {
                $this->attach($model, $pivotData);
            }
            else {
                $this->parent->bindEventOnce('model.afterSave', function () use ($model, $pivotData) {
                    $this->attach($model, $pivotData);
                });
            }

            $this->parent->reloadRelations($this->relationName);
        }
        else {
            $this->parent->bindDeferred($this->relationName, $model, $sessionKey, $pivotData);
        }
    }

    /**
     * remove a model from this relationship type.
     */
    public function remove(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            $this->detach($model);
            $this->parent->reloadRelations($this->relationName);
        }
        else {
            $this->parent->unbindDeferred($this->relationName, $model, $sessionKey);
        }
    }

    /**
     * paginate gets a paginator for the "select" statement that complies with October Rain
     *
     * @param  int    $perPage
     * @param  int    $currentPage
     * @param  array  $columns
     * @param  string  $pageName
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $currentPage = null)
    {
        // Legacy signature support
        // paginate($perPage, $currentPage, $columns, $pageName)
        if (!is_array($columns)) {
            $_currentPage = $columns;
            $_columns = $pageName;
            $_pageName = $currentPage;

            $columns = is_array($_columns) ? $_columns : ['*'];
            $pageName = $_pageName !== null ? $_pageName : 'page';
            $currentPage = is_array($_currentPage) ? null : $_currentPage;
        }

        $this->query->addSelect($this->shouldSelect($columns));

        $paginator = $this->query->paginate($perPage, $currentPage, $columns);

        $this->hydratePivotRelation($paginator->items());

        return $paginator;
    }

    /**
     * simplePaginate using a simple paginator.
     *
     * @param  int|null  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginate($perPage = null, $columns = ['*'], $pageName = 'page', $currentPage = null)
    {
        // Legacy signature support
        // paginate($perPage, $currentPage, $columns, $pageName)
        if (!is_array($columns)) {
            $_currentPage = $columns;
            $_columns = $pageName;
            $_pageName = $currentPage;

            $columns = is_array($_columns) ? $_columns : ['*'];
            $pageName = $_pageName !== null ? $_pageName : 'page';
            $currentPage = is_array($_currentPage) ? null : $_currentPage;
        }

        $this->query->addSelect($this->shouldSelect($columns));

        $paginator = $this->query->simplePaginate($perPage, $currentPage, $columns);

        $this->hydratePivotRelation($paginator->items());

        return $paginator;
    }

    /**
     * newPivot creates a new pivot model instance
     *
     * @param  array  $attributes
     * @param  bool   $exists
     * @return \Illuminate\Database\Eloquent\Relations\Pivot
     */
    public function newPivot(array $attributes = [], $exists = false)
    {
        /*
         * October looks to the relationship parent
         */
        $pivot = $this->parent->newRelationPivot($this->relationName, $this->parent, $attributes, $this->table, $exists);

        /*
         * Laravel looks to the related model
         */
        if (empty($pivot)) {
            $pivot = $this->related->newPivot($this->parent, $attributes, $this->table, $exists, $this->using);
        }

        return $pivot->setPivotKeys($this->foreignPivotKey, $this->relatedPivotKey);
    }

    /**
     * setSimpleValue helper for setting this relationship using various expected
     * values. For example, $model->relation = $value;
     */
    public function setSimpleValue($value)
    {
        /*
         * Nulling the relationship
         */
        if (!$value) {
            // Disassociate in memory immediately
            $this->parent->setRelation(
                $this->relationName,
                $this->getRelated()->newCollection()
            );

            // Perform sync when the model is saved
            $this->parent->bindEventOnce('model.afterSave', function () use ($value) {
                $this->detach();
            });
            return;
        }

        /*
         * Convert models to keys
         */
        if ($value instanceof Model) {
            $value = $value->getKey();
        }
        elseif (is_array($value)) {
            foreach ($value as $_key => $_value) {
                if ($_value instanceof Model) {
                    $value[$_key] = $_value->getKey();
                }
            }
        }

        /*
         * Setting the relationship
         */
        $relationCollection = $value instanceof CollectionBase
            ? $value
            : $this->newSimpleRelationQuery((array) $value)->get();

        // Associate in memory immediately
        $this->parent->setRelation($this->relationName, $relationCollection);

        // Perform sync when the model is saved
        $this->parent->bindEventOnce('model.afterSave', function () use ($value) {
            $this->sync($value);
        });
    }

    /**
     * newSimpleRelationQuery for the related instance based on an array of IDs.
     */
    protected function newSimpleRelationQuery(array $ids)
    {
        $model = $this->getRelated();

        $query = $model->newQuery();

        return $query->whereIn($model->getKeyName(), $ids);
    }

    /**
     * getSimpleValue is a helper for getting this relationship simple value,
     * generally useful with form values
     */
    public function getSimpleValue()
    {
        $value = [];

        $relationName = $this->relationName;

        $sessionKey = $this->parent->sessionKey;

        if ($this->parent->relationLoaded($relationName)) {
            $related = $this->getRelated();

            $value = $this->parent->getRelation($relationName)->pluck($related->getKeyName())->all();
        }
        else {
            $value = $this->allRelatedIds($sessionKey)->all();
        }

        return $value;
    }

    /**
     * allRelatedIds for the related models, with deferred binding support
     * @param string $sessionKey
     * @return \October\Rain\Support\Collection
     */
    public function allRelatedIds($sessionKey = null)
    {
        $related = $this->getRelated();

        $fullKey = $related->getQualifiedKeyName();

        $query = $sessionKey ? $this->withDeferred($sessionKey) : $this;

        return $query->getQuery()->select($fullKey)->pluck($related->getKeyName());
    }

    /**
     * getForeignKey gets the fully qualified foreign key for the relation
     * @return string
     */
    public function getForeignKey()
    {
        return $this->table.'.'.$this->foreignPivotKey;
    }

    /**
     * getOtherKey gets the fully qualified "other key" for the relation
     * @return string
     */
    public function getOtherKey()
    {
        return $this->table.'.'.$this->relatedPivotKey;
    }
}
