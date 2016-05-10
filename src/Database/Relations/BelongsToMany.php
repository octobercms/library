<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany as BelongsToManyBase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection as CollectionBase;

class BelongsToMany extends BelongsToManyBase
{
    use DeferOneOrMany;

    /**
     * @var boolean This relation object is a 'count' helper.
     */
    public $countMode = false;

    /**
     * Set the select clause for the relation query.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    protected function getSelectColumns(array $columns = ['*'])
    {
        if ($this->countMode) {
            return $this->table.'.'.$this->foreignKey.' as pivot_'.$this->foreignKey;
        }

        if ($columns == ['*']) {
            $columns = [$this->related->getTable().'.*'];
        }

        return array_merge($columns, $this->getAliasedPivotColumns());
    }

    /**
     * Save the supplied related model with deferred binding support.
     */
    public function save(Model $model, array $pivotData = [], $sessionKey = null)
    {
        $model->save();
        $this->add($model, $sessionKey, $pivotData);
        return $model;
    }

    /**
     * Create a new instance of this related model with deferred binding support.
     */
    public function create(array $attributes = [], array $pivotData = [], $sessionKey = null)
    {
        $model = $this->related->create($attributes);

        $this->add($model, $sessionKey, $pivotData);

        return $model;
    }

    /**
     * Adds a model to this relationship type.
     */
    public function add(Model $model, $sessionKey = null, $pivotData = [])
    {
        if ($sessionKey === null) {
            $this->attach($model->getKey(), $pivotData);
            $this->parent->reloadRelations($this->relationName);
        }
        else {
            $this->parent->bindDeferred($this->relationName, $model, $sessionKey);
        }
    }

    /**
     * Removes a model from this relationship type.
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
     * Set the left join clause for the relation query, used by DeferOneOrMany.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null
     * @return $this
     */
    protected function setLeftJoin($query = null)
    {
        $query = $query ?: $this->query;

        $baseTable = $this->related->getTable();

        $key = $baseTable.'.'.$this->related->getKeyName();

        $query->leftJoin($this->table, $key, '=', $this->getOtherKey());

        return $this;
    }

    /**
     * Get a paginator for the "select" statement. Complies with October Rain.
     *
     * @param  int    $perPage
     * @param  int    $currentPage
     * @param  array  $columns
     * @param  string  $pageName
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15, $currentPage = null, $columns = ['*'], $pageName = 'page')
    {
        $this->query->addSelect($this->getSelectColumns($columns));
        $paginator = $this->query->paginate($perPage, $currentPage, $columns);
        $this->hydratePivotRelation($paginator->items());
        return $paginator;
    }

    /**
     * Create a new pivot model instance.
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
            $pivot = $this->related->newPivot($this->parent, $attributes, $this->table, $exists);
        }

        return $pivot->setPivotKeys($this->foreignKey, $this->otherKey);
    }

    /**
     * Helper for setting this relationship using various expected
     * values. For example, $model->relation = $value;
     */
    public function setSimpleValue($value)
    {
        // Nulling the relationship
        if (!$value) {
            if ($this->parent->exists) {
                $this->detach();
                $this->parent->reloadRelations($this->relationName);
            }
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

        if (is_string($value)) {
            $value = [$value];
        }

        // Do not sync until the model is saved
        $this->parent->bindEventOnce('model.afterSave', function() use ($value){
            $this->sync($value);
        });

        $relationModel = $this->getRelated();
        $relationCollection = $value instanceof CollectionBase
            ? $value
            : $relationModel->whereIn($relationModel->getKeyName(), $value)->get();

        // Associate
        $this->parent->setRelation($this->relationName, $relationCollection);
    }

    /**
     * Helper for getting this relationship simple value,
     * generally useful with form values.
     */
    public function getSimpleValue()
    {
        $value = [];

        $relationName = $this->relationName;

        $sessionKey = $this->parent->sessionKey;

        if ($this->parent->relationLoaded($relationName)) {
            $related = $this->getRelated();

            $value = $this->parent->getRelation($relationName)->lists($related->getKeyName());
        }
        else {
            $value = $this->getRelatedIds($sessionKey);
        }

        return $value;
    }

    /**
     * Get all of the IDs for the related models, with deferred binding support
     *
     * @param string $sessionKey
     * @return \October\Rain\Support\Collection
     */
    public function getRelatedIds($sessionKey = null)
    {
        $related = $this->getRelated();

        $fullKey = $related->getQualifiedKeyName();

        $query = $sessionKey ? $this->withDeferred($sessionKey) : $this;

        return $query->getQuery()->select($fullKey)->lists($related->getKeyName());
    }

}
