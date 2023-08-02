<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsToMany as BelongsToManyBase;

/**
 * DefinedConstraints handles the constraints and filters defined by a relation
 * eg: 'conditions' => 'is_published = 1'
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait DefinedConstraints
{
    /**
     * addDefinedConstraints to the relation query
     */
    public function addDefinedConstraints(): void
    {
        $args = $this->parent->getRelationDefinition($this->relationName);

        $this->addDefinedConstraintsToRelation($this, $args);

        $this->addDefinedConstraintsToQuery($this, $args);
    }

    /**
     * addDefinedConstraintsToRelation
     */
    public function addDefinedConstraintsToRelation($relation, array $args = null)
    {
        if ($args === null) {
            $args = $this->parent->getRelationDefinition($this->relationName);
        }

        // Default models (belongsTo, hasOne, hasOneThrough, morphOne)
        if ($defaultData = array_get($args, 'default')) {
            $relation->withDefault($defaultData);
        }

        // Pivot data (belongsToMany, morphToMany, morphByMany)
        if ($pivotData = array_get($args, 'pivot')) {
            $relation->withPivot($pivotData);
        }

        // Pivot timestamps (belongsToMany, morphToMany, morphByMany)
        if (array_get($args, 'timestamps')) {
            $relation->withTimestamps();
        }

        // Count "helper" relation
        // @deprecated use Laravel withCount() method instead
        if (array_get($args, 'count')) {
            if ($relation instanceof BelongsToManyBase) {
                $relation->countMode = true;
                $keyName = $relation->getQualifiedForeignPivotKeyName();
            }
            else {
                $keyName = $relation->getForeignKeyName();
            }

            $countSql = $this->parent->getConnection()->raw('count(*) as count');

            $relation->select($keyName, $countSql)->groupBy($keyName)->orderBy($keyName);
        }
    }

    /**
     * addDefinedConstraintsToQuery
     */
    public function addDefinedConstraintsToQuery($query, array $args = null)
    {
        if ($args === null) {
            $args = $this->parent->getRelationDefinition($this->relationName);
        }

        // Conditions
        if ($conditions = array_get($args, 'conditions')) {
            $query->whereRaw($conditions);
        }

        // Sort order
        // @deprecated count is deprecated
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
                    [$column, $direction] = $parts;
                }

                $query->orderBy($column, $direction);
            }
        }

        // Scope
        if ($scope = array_get($args, 'scope')) {
            if (is_string($scope)) {
                $query->$scope($this->parent);
            }
            else {
                $scope($query, $this->parent, $this->related);
            }
        }
    }
}
