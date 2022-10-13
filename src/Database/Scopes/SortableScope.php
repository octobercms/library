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
        $builder->getQuery()->orderBy($model->getQualifiedSortOrderColumn());
    }

    /**
     * extend the Eloquent query builder.
     */
    public function extend(BuilderBase $builder)
    {
        $removeOnMethods = ['reorder', 'orderBy', 'groupBy'];

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
