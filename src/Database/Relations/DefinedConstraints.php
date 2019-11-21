<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany as BelongsToManyBase;

/*
 * Handles the constraints and filters defined by a relation.
 * eg: 'conditions' => 'is_published = 1'
 */
trait DefinedConstraints
{

    /**
     * Set the defined constraints on the relation query.
     *
     * @return void
     */
    public function addDefinedConstraints()
    {
        $args = $this->parent->getRelationDefinition($this->relationName);

        $this->addDefinedConstraintsToRelation($this, $args);

        $this->addDefinedConstraintsToQuery($this, $args);
    }

    /**
     * Add relation based constraints.
     *
     * @param Illuminate\Database\Eloquent\Relations\Relation $relation
     * @param array $args
     */
    public function addDefinedConstraintsToRelation($relation, $args = null)
    {
        if ($args === null) {
            $args = $this->parent->getRelationDefinition($this->relationName);
        }

        /*
         * Default models (belongsTo)
         */
        if ($defaultData = array_get($args, 'default')) {
            $relation->withDefault($defaultData === true ? null : $defaultData);
        }

        /*
         * Pivot data (belongsToMany, morphToMany, morphByMany)
         */
        if ($pivotData = array_get($args, 'pivot')) {
            $relation->withPivot($pivotData);
        }

        /*
         * Pivot timestamps (belongsToMany, morphToMany, morphByMany)
         */
        if (array_get($args, 'timestamps')) {
            $relation->withTimestamps();
        }

        /*
         * Count "helper" relation
         */
        if ($count = array_get($args, 'count')) {
            if ($relation instanceof BelongsToManyBase) {
                $relation->countMode = true;
            }

            $countSql = $this->parent->getConnection()->raw('count(*) as count');

            $relation
                ->select($relation->getForeignKey(), $countSql)
                ->groupBy($relation->getForeignKey())
                ->orderBy($relation->getForeignKey())
            ;
        }
    }

    /**
     * Add query based constraints.
     *
     * @param October\Rain\Database\QueryBuilder $query
     * @param array $args
     */
    public function addDefinedConstraintsToQuery($query, $args = null)
    {
        if ($args === null) {
            $args = $this->parent->getRelationDefinition($this->relationName);
        }

        /*
         * Conditions
         */
        if ($conditions = array_get($args, 'conditions')) {
            $query->whereRaw($conditions);
        }

        /*
         * Sort order
         */
        $hasCountArg = array_get($args, 'count') !== null;
        if (($orderBy = array_get($args, 'order')) && !$hasCountArg) {
            if (!is_array($orderBy)) {
                $orderBy = [$orderBy];
            }

            foreach ($orderBy as $order) {
                $column = $order;
                $direction = 'asc';

                $parts = explode(' ', $order);
                if (count($parts) > 1) {
                    list($column, $direction) = $parts;
                }

                $query->orderBy($column, $direction);
            }
        }

        /*
         * Scope
         */
        if ($scope = array_get($args, 'scope')) {
            $query->$scope($this->parent);
        }
    }
}
