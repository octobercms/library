<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as CollectionBase;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as BelongsToManyBase;

/**
 * BelongsToMany
 */
class BelongsToMany extends BelongsToManyBase
{
    use DeferOneOrMany;
    use DefinedConstraints;

    /**
     * @var boolean countMode sets this relation object is a 'count' helper
     * @deprecated use Laravel withCount() method instead
     */
    public $countMode = false;

    /**
     * @var boolean orphanMode used when a join is not used, don't select aliased columns
     */
    public $orphanMode = false;

    /**
     * Create a new belongs to many relationship instance.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $table
     * @param  string  $foreignPivotKey
     * @param  string  $relatedPivotKey
     * @param  string  $relationName
     * @return void
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
     * @param mixed $id
     * @param array $attributes
     * @param bool  $touch
     */
    public function attach($id, array $attributes = [], $touch = true)
    {
        /**
         * @event model.relation.beforeAttach
         * Called before creating a new relation between models (only for BelongsToMany relation)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.beforeAttach', function (string $relationName, mixed $id, array $insertData) use (\October\Rain\Database\Model $model) {
         *         if (!$model->isRelationValid($id)) {
         *             return false;
         *         }
         *     });
         *
         */
        if ($this->parent->fireEvent('model.relation.beforeAttach', [$this->relationName, &$id, &$attributes], true) === false) {
            return;
        }

        /*
         * See \Illuminate\Database\Eloquent\Relations\Concerns\InteractsWithPivotTable
         */
        parent::attach($id, $attributes, $touch);

        /**
         * @event model.relation.attach
         * Called after creating a new relation between models (only for BelongsToMany relation)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.attach', function (string $relationName, mixed $id, array $insertData) use (\October\Rain\Database\Model $model) {
         *         traceLog("New relation {$relationName} was created", $id);
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.attach', [$this->relationName, $id, $attributes]);
    }

    /**
     * detach overrides detach() method of BelongToMany relation.
     * This is necessary in order to fire 'model.relation.beforeDetach', 'model.relation.detach' events
     * @param null $ids
     * @param bool $touch
     * @return int|void
     */
    public function detach($ids = null, $touch = true)
    {
        /**
         * @event model.relation.beforeDetach
         * Called before removing a relation between models (only for BelongsToMany relation)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.beforeDetach', function (string $relationName, array $ids) use (\October\Rain\Database\Model $model) {
         *         if (!$model->isRelationValid($ids)) {
         *             return false;
         *         }
         *     });
         *
         */
        if ($this->parent->fireEvent('model.relation.beforeDetach', [$this->relationName, &$ids], true) === false) {
            return;
        }

        /*
         * See \Illuminate\Database\Eloquent\Relations\Concerns\InteractsWithPivotTable
         */
        $result = parent::detach($ids, $touch);

        /**
         * @event model.relation.detach
         * Called after removing a relation between models (only for BelongsToMany relation)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.detach', function (string $relationName, array $ids) use (\October\Rain\Database\Model $model) {
         *         traceLog("Relation {$relationName} was removed", $ids);
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.detach', [$this->relationName, $ids, $result]);
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

        if ($sessionKey === null) {
            $this->attach($model->getKey(), $pivotData);
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
            $this->detach($model->getKey());
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
    public function paginate($perPage = 15, $currentPage = null, $columns = ['*'], $pageName = 'page')
    {
        $this->query->addSelect($this->shouldSelect($columns));

        $paginator = $this->query->paginate($perPage, $currentPage, $columns);

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
     * setSimpleValue is a helper for setting this relationship using various expected
     * values. For example, $model->relation = $value;
     */
    public function setSimpleValue($value)
    {
        $relationModel = $this->getRelated();

        /*
         * Nulling the relationship
         */
        if (!$value) {
            // Disassociate in memory immediately
            $this->parent->setRelation($this->relationName, $relationModel->newCollection());

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
         * Convert scalar to array
         */
        if (!is_array($value) && !$value instanceof CollectionBase) {
            $value = [$value];
        }

        /*
         * Setting the relationship
         */
        $relationCollection = $value instanceof CollectionBase
            ? $value
            : $relationModel->whereIn($relationModel->getKeyName(), $value)->get();

        // Associate in memory immediately
        $this->parent->setRelation($this->relationName, $relationCollection);

        // Perform sync when the model is saved
        $this->parent->bindEventOnce('model.afterSave', function () use ($value) {
            $this->sync($value);
        });
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
