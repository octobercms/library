<?php namespace October\Rain\Database\Relations;

use Site;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as CollectionBase;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as BelongsToManyBase;
use October\Rain\Support\Facades\DbDongle;

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
     * addWhereConstraints sets the where clause for the relation query.
     * @return $this
     */
    protected function addWhereConstraints()
    {
        parent::addWhereConstraints();

        $this->addPivotSiteScopeConstraints();

        return $this;
    }

    /**
     * addEagerConstraints sets the constraints for an eager load of the relation.
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        parent::addEagerConstraints($models);

        $this->addPivotSiteScopeConstraints();
    }

    /**
     * baseAttachRecord creates a new pivot attachment record.
     * @param  int   $id
     * @param  bool  $timed
     * @return array
     */
    protected function baseAttachRecord($id, $timed)
    {
        $record = parent::baseAttachRecord($id, $timed);

        if ($siteId = $this->getPivotSiteScopeValue()) {
            $record['site_id'] = $siteId;
        }

        return $record;
    }

    /**
     * getRelationExistenceQuery adds the constraints for a relationship count query.
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $query = parent::getRelationExistenceQuery($query, $parentQuery, $columns);

        if ($this->pivotSiteScope) {
            $query->where($this->qualifyPivotColumn('site_id'), Site::getSiteIdFromContext());
        }

        return $query;
    }

    /**
     * newPivotQuery creates a new query builder for the pivot table.
     */
    public function newPivotQuery()
    {
        $query = parent::newPivotQuery();

        if ($this->pivotSiteScope) {
            $query->where($this->table.'.site_id', Site::getSiteIdFromContext());
        }

        return $query;
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
     * saveQuietly saves the model without raising any events,
     * with deferred binding support.
     */
    public function saveQuietly(Model $model, array $pivotData = [], $sessionKey = null)
    {
        return Model::withoutEvents(function () use ($model, $pivotData, $sessionKey) {
            return $this->save($model, $pivotData, $sessionKey);
        });
    }

    /**
     * saveMany saves multiple models with deferred binding support.
     */
    public function saveMany($models, array $pivotData = [], $sessionKey = null)
    {
        foreach ($models as $model) {
            $this->save($model, $pivotData, $sessionKey);
        }

        return $models;
    }

    /**
     * saveManyQuietly saves multiple models without raising any events,
     * with deferred binding support.
     */
    public function saveManyQuietly($models, array $pivotData = [], $sessionKey = null)
    {
        return Model::withoutEvents(function () use ($models, $pivotData, $sessionKey) {
            return $this->saveMany($models, $pivotData, $sessionKey);
        });
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
     * createQuietly creates a new instance without raising any events,
     * with deferred binding support.
     */
    public function createQuietly(array $attributes = [], array $pivotData = [], $sessionKey = null)
    {
        return Model::withoutEvents(function () use ($attributes, $pivotData, $sessionKey) {
            return $this->create($attributes, $pivotData, $sessionKey);
        });
    }

    /**
     * createMany creates multiple related models with deferred binding support.
     */
    public function createMany(iterable $records, array $pivotData = [], $sessionKey = null)
    {
        $instances = $this->related->newCollection();

        foreach ($records as $record) {
            $instances->push($this->create($record, $pivotData, $sessionKey));
        }

        return $instances;
    }

    /**
     * createManyQuietly creates multiple models without raising any events,
     * with deferred binding support.
     */
    public function createManyQuietly(iterable $records, array $pivotData = [], $sessionKey = null)
    {
        return Model::withoutEvents(function () use ($records, $pivotData, $sessionKey) {
            return $this->createMany($records, $pivotData, $sessionKey);
        });
    }

    /**
     * createOrFirst attempts to create the record, or if a unique constraint
     * violation occurs, finds the existing record.
     */
    public function createOrFirst(array $attributes = [], \Closure|array $values = [], array $pivotData = [], $sessionKey = null)
    {
        $model = $this->related->createOrFirst($attributes, $values);

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
         *     $model->bindEvent('model.relation.beforeDetach', function (string $relationName, ?array $parsedIds) use (\October\Rain\Database\Model $model) {
         *         foreach ((array) $parsedIds as $id) {
         *             if (!$model->isRelationValid($parsedIds)) {
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
        $results = parent::detach($parsedIds, $touch);

        /**
         * @event model.relation.detach
         * Called after removing a relation between models (only for BelongsToMany relation)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.detach', function (string $relationName, ?array $parsedIds, int $results) use (\October\Rain\Database\Model $model) {
         *         foreach ($ids as $id) {
         *             traceLog("Relation {$relationName} was removed", (array) $parsedIds);
         *         }
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.detach', [$this->relationName, $parsedIds, $results]);
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

            $this->parent->unsetRelation($this->relationName);
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
            $this->parent->unsetRelation($this->relationName);
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
     * cursorPaginate using a cursor paginator.
     *
     * @param  int|null  $perPage
     * @param  array  $columns
     * @param  string  $cursorName
     * @param  \Illuminate\Pagination\Cursor|string|null  $cursor
     * @return \Illuminate\Contracts\Pagination\CursorPaginator
     */
    public function cursorPaginate($perPage = null, $columns = ['*'], $cursorName = 'cursor', $cursor = null)
    {
        $this->query->addSelect($this->shouldSelect($columns));

        $paginator = $this->query->cursorPaginate($perPage, $columns, $cursorName, $cursor);

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
        // October looks to the relationship parent
        $pivot = $this->parent->newRelationPivot($this->relationName, $this->parent, $attributes, $this->table, $exists);

        // Laravel looks to the related model
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
        // Nulling the relationship
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

        // Convert models to keys
        if ($value instanceof Model) {
            $value = $value->{$this->getRelatedKeyName()};
        }
        elseif (is_array($value)) {
            foreach ($value as $_key => $_value) {
                if ($_value instanceof Model) {
                    $value[$_key] = $_value->{$this->getRelatedKeyName()};
                }
            }
        }

        // Setting the relationship
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

        return $query->whereIn($this->getRelatedKeyName(), $ids);
    }

    /**
     * getSimpleValue is a helper for getting this relationship simple value,
     * generally useful with form values
     */
    public function getSimpleValue()
    {
        $value = [];
        $relationName = $this->relationName;

        if ($this->parent->relationLoaded($relationName)) {
            $value = $this->parent->getRelation($relationName)
                ->pluck($this->getRelatedKeyName())
                ->all()
            ;
        }
        else {
            $value = $this->allRelatedIds()->all();
        }

        return $value;
    }

    /**
     * @deprecated use getQualifiedForeignPivotKeyName
     */
    public function getForeignKey()
    {
        return $this->table.'.'.$this->foreignPivotKey;
    }

    /**
     * @deprecated use getQualifiedRelatedPivotKeyName
     */
    public function getOtherKey()
    {
        return $this->table.'.'.$this->relatedPivotKey;
    }

    /**
     * shouldSelect gets the select columns for the relation query
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function shouldSelect(array $columns = ['*'])
    {
        // @deprecated remove this whole method when `countMode` is gone
        if ($this->countMode) {
            return $this->table.'.'.$this->foreignPivotKey.' as pivot_'.$this->foreignPivotKey;
        }

        if ($columns === ['*']) {
            $columns = [$this->related->getTable().'.*'];
        }

        return array_merge($columns, $this->aliasedPivotColumns());
    }

    /**
     * performJoin will join the pivot table opportunistically instead of mandatorily
     * to support deferred bindings that exist in another table.
     *
     * This method is based on `performJoin` method logic except it uses a left join.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $query
     * @return $this
     */
    protected function performLeftJoin($query = null)
    {
        $query = $query ?: $this->query;

        $query->leftJoin($this->table, function($join) {
            $join->on($this->getQualifiedRelatedKeyName(), '=', $this->getQualifiedRelatedPivotKeyName());
            $join->where($this->getQualifiedForeignPivotKeyName(), $this->parent->getKey());
        });

        return $this;
    }

    /**
     * performSortableColumnJoin includes custom logic to replace the sort order column with
     * a unified column
     */
    protected function performSortableColumnJoin($query = null, $sessionKey = null)
    {
        if (
            !$this->parent->isClassInstanceOf(\October\Contracts\Database\SortableRelationInterface::class) ||
            !$this->parent->isSortableRelation($this->relationName)
        ) {
            return;
        }

        // Check if sorting by the matched sort_order column
        $sortColumn = $this->qualifyPivotColumn(
            $this->parent->getRelationSortOrderColumn($this->relationName)
        );

        $orderDefinitions = $query->getQuery()->orders;

        if (!is_array($orderDefinitions)) {
            return;
        }

        $sortableIndex = false;
        foreach ($orderDefinitions as $index => $order) {
            if ($order['column'] === $sortColumn) {
                $sortableIndex = $index;
            }
        }

        // Not sorting by the sort column, abort
        if ($sortableIndex === false) {
            return;
        }

        // Join the deferred binding table and select the combo column
        $tempOrderColumns = 'october_reserved_sort_order';
        $combinedOrderColumn = "ifnull(deferred_bindings.sort_order, {$sortColumn}) as {$tempOrderColumns}";
        $this->performDeferredLeftJoin($query, $sessionKey);
        $this->addSelect(DbDongle::raw($combinedOrderColumn));

        // Overwrite the sortable column with the combined one
        $query->getQuery()->orders[$sortableIndex]['column'] = $tempOrderColumns;
    }

    /**
     * performDeferredLeftJoin left joins the deferred bindings table
     */
    protected function performDeferredLeftJoin($query = null, $sessionKey = null)
    {
        $query = $query ?: $this->query;

        $query->leftJoin('deferred_bindings', function($join) use ($sessionKey) {
            $join->on(
                $this->getQualifiedRelatedKeyName(), '=', 'deferred_bindings.slave_id')
                    ->where('master_field', $this->relationName)
                    ->where('master_type', get_class($this->parent))
                    ->where('session_key', $sessionKey);
        });

        return $this;
    }
}
