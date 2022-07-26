<?php namespace October\Rain\Database\Scopes;

use Illuminate\Database\Eloquent\Model as ModelBase;
use Illuminate\Database\Eloquent\Scope as ScopeInterface;
use Illuminate\Database\Eloquent\Builder as BuilderBase;

/**
 * SortableScope will apply default sort ordering
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class SortableScope implements ScopeInterface
{
    /**
     * apply the scope to a given Eloquent query builder.
     */
    public function apply(BuilderBase $builder, ModelBase $model)
    {
        if ($this->calledByAggregator($builder)) {
            return;
        }

        $builder->getQuery()->orderBy($model->getQualifiedSortOrderColumn());
    }

    /**
     * check if the current query is called by an aggregator, i.e. when used with
     * useRelationCount, by checking if it contains a count(*) statement
     */
    protected function calledByAggregator(BuilderBase $builder): bool
    {
        if (empty($builder->getQuery()->columns)) {
            return false;
        }

        foreach ($builder->getQuery()->columns as $column) {
            if (strtolower((string)$column) === 'count(*)') {
                return true;
            }
        }

        return false;
    }

    /**
     * extend the Eloquent query builder.
     */
    public function extend(BuilderBase $builder)
    {
        $removeOnMethods = ['orderBy', 'groupBy'];

        foreach ($removeOnMethods as $method) {
            $builder->macro($method, function ($builder, ...$args) use ($method) {
                $builder
                    ->withoutGlobalScope($this)
                    ->getQuery()
                    ->$method(...$args)
                ;

                return $builder;
            });
        }
    }
}
