<?php namespace October\Rain\Database\Relations;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use October\Rain\Database\MorphPivot;

/**
 * MorphToMany
 *
 * This class is a carbon copy of Illuminate\Database\Eloquent\Relations\MorphToMany
 * so the base October\Rain\Database\Relations\BelongsToMany class can be inherited
 */
class MorphToMany extends BelongsToMany
{
    use DefinedConstraints;

    /**
     * @var string morphType is type of the polymorphic relation
     */
    protected $morphType;

    /**
     * @var string morphClass is the class name of the morph type constraint
     */
    protected $morphClass;

    /**
     * @var bool inverse indicates if we are connecting the inverse of the relation.
     * This primarily affects the morphClass constraint.
     */
    protected $inverse;

    /**
     * __construct will create a new morph to many relationship instance
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Model  $parent
     * @param  string  $name
     * @param  string  $table
     * @param  string  $foreignKey
     * @param  string  $otherKey
     * @param  string  $relationName
     * @param  bool  $inverse
     * @return void
     */
    public function __construct(
        Builder $query,
        Model $parent,
        $name,
        $table,
        $foreignKey,
        $otherKey,
        $parentKey,
        $relatedKey,
        $relationName = null,
        $inverse = false
    ) {
        $this->inverse = $inverse;

        $this->morphType = $name.'_type';

        $this->morphClass = $inverse ? $query->getModel()->getMorphClass() : $parent->getMorphClass();

        parent::__construct(
            $query,
            $parent,
            $table,
            $foreignKey,
            $otherKey,
            $parentKey,
            $relatedKey,
            $relationName
        );

        $this->addDefinedConstraints();
    }

    /**
     * addWhereConstraints set the where clause for the relation query
     * @return $this
     */
    protected function addWhereConstraints()
    {
        parent::addWhereConstraints();

        $this->query->where($this->table.'.'.$this->morphType, $this->morphClass);

        return $this;
    }

    /**
     * addEagerConstraints sets the constraints for an eager load of the relation
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        parent::addEagerConstraints($models);

        $this->query->where($this->table.'.'.$this->morphType, $this->morphClass);
    }

    /**
     * baseAttachRecord creates a new pivot attachment record.
     * @param  int   $id
     * @param  bool  $timed
     * @return array
     */
    protected function baseAttachRecord($id, $timed)
    {
        return Arr::add(
            parent::baseAttachRecord($id, $timed),
            $this->morphType,
            $this->morphClass
        );
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
        return parent::getRelationExistenceQuery($query, $parentQuery, $columns)->where(
            $this->table.'.'.$this->morphType,
            $this->morphClass
        );
    }

    /**
     * newPivotQuery creates a new query builder for the pivot table.
     */
    public function newPivotQuery()
    {
        return parent::newPivotQuery()->where($this->morphType, $this->morphClass);
    }

    /**
     * newPivot creates a new pivot model instance
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
         * Laravel creates new pivot model this way
         */
        if (empty($pivot)) {
            $using = $this->using;

            $pivot = $using ? $using::fromRawAttributes($this->parent, $attributes, $this->table, $exists)
                            : new MorphPivot($this->parent, $attributes, $this->table, $exists);
        }

        $pivot->setPivotKeys($this->foreignPivotKey, $this->relatedPivotKey)
              ->setMorphType($this->morphType)
              ->setMorphClass($this->morphClass);

        return $pivot;
    }

    /**
     * getMorphType gets the foreign key "type" name
     */
    public function getMorphType()
    {
        return $this->morphType;
    }

    /**
     * getMorphClass get the class name of the parent model
     */
    public function getMorphClass()
    {
        return $this->morphClass;
    }
}
