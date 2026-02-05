<?php namespace October\Rain\Database\Relations;

use Site;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as BelongsToManyBase;
use Illuminate\Support\Arr;

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
     * @var bool pivotSiteScope indicates if pivot queries should be scoped by site_id.
     * This is used for dual-multisite scenarios where both parent and related models use multisite.
     */
    protected $pivotSiteScope = false;

    /**
     * addDefinedConstraints to the relation query
     */
    public function addDefinedConstraints(): void
    {
        $args = $this->getRelationDefinitionForDefinedConstraints();

        $this->addDefinedConstraintsToRelation($this, $args);

        $this->addDefinedConstraintsToQuery($this, $args);
    }

    /**
     * addDefinedConstraintsToRelation
     */
    public function addDefinedConstraintsToRelation($relation, ?array $args = null)
    {
        if ($args === null) {
            $args = $this->getRelationDefinitionForDefinedConstraints();
        }

        // Default models (belongsTo, hasOne, hasOneThrough, morphOne)
        if ($defaultData = Arr::get($args, 'default')) {
            $relation->withDefault($defaultData);
        }

        // Pivot data (belongsToMany, morphToMany, morphByMany)
        if ($pivotData = Arr::get($args, 'pivot')) {
            $relation->withPivot($pivotData);
        }

        // Pivot incrementing key (belongsToMany, morphToMany, morphByMany)
        if ($pivotKey = Arr::get($args, 'pivotKey')) {
            $relation->withPivot($pivotKey);
        }

        // Pivot timestamps (belongsToMany, morphToMany, morphByMany)
        if (Arr::get($args, 'timestamps')) {
            $relation->withTimestamps();
        }

        // Count "helper" relation
        // @deprecated use Laravel withCount() method instead
        if (Arr::get($args, 'count')) {
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

        // Pivot site scope (dual-multisite: both parent and related models use multisite)
        // This enables site_id scoping on pivot table queries
        if (Arr::get($args, 'pivotSiteScope')) {
            $this->pivotSiteScope = true;
            $relation->withPivot(['site_id']);
        }
    }

    /**
     * isPivotSiteScoped returns true if pivot queries should be scoped by site_id.
     */
    public function isPivotSiteScoped(): bool
    {
        return $this->pivotSiteScope;
    }

    /**
     * addPivotSiteScopeConstraints adds site_id constraint to pivot queries if enabled.
     */
    protected function addPivotSiteScopeConstraints(): void
    {
        if ($this->pivotSiteScope && !Site::hasGlobalContext()) {
            $this->where($this->qualifyPivotColumn('site_id'), Site::getSiteIdFromContext());
        }
    }

    /**
     * getPivotSiteScopeValue returns the current site_id value for pivot records.
     * Returns null if pivotSiteScope is disabled, otherwise returns the site ID.
     */
    protected function getPivotSiteScopeValue(): ?int
    {
        if (!$this->pivotSiteScope) {
            return null;
        }

        $siteId = Site::getSiteIdFromContext();

        // In dual-multisite, we always need a site_id for pivot records
        // If there's no site context, it likely means we're in global context
        // during propagation - the sync should happen inside Site::withContext()
        if ($siteId === null) {
            return null;
        }

        return $siteId;
    }

    /**
     * addDefinedConstraintsToQuery
     */
    public function addDefinedConstraintsToQuery($query, ?array $args = null)
    {
        if ($args === null) {
            $args = $this->getRelationDefinitionForDefinedConstraints();
        }

        // Conditions
        if ($conditions = Arr::get($args, 'conditions')) {
            $query->whereRaw($conditions);
        }

        // Sort order
        // @deprecated count is deprecated
        $hasCountArg = Arr::get($args, 'count') !== null;
        if (($orderBy = Arr::get($args, 'order')) && !$hasCountArg) {
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
        if ($scope = Arr::get($args, 'scope')) {
            if (is_string($scope)) {
                $query->$scope($this->parent);
            }
            else {
                $scope($query, $this->parent, $this->related);
            }
        }
    }

    /**
     * getRelationDefinitionForDefinedConstraints returns the relation definition for the
     * relationship context.
     */
    protected function getRelationDefinitionForDefinedConstraints()
    {
        return $this->parent->getRelationDefinition($this->relationName);
    }
}
