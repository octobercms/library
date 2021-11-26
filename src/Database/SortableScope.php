<?php namespace October\Rain\Database;

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
     * @var bool scopeApplied determines if the scope has been applied.
     */
    protected $scopeApplied;

    /**
     * apply the scope to a given Eloquent query builder.
     */
    public function apply(BuilderBase $builder, ModelBase $model)
    {
        $this->scopeApplied = true;

        $builder->getQuery()->orderBy($model->getQualifiedSortOrderColumn());
    }

    /**
     * extend the Eloquent query builder.
     */
    public function extend(BuilderBase $builder)
    {
        $builder->macro('orderBy', function ($builder, $column, $direction = 'asc') {
            $builder
                ->withoutGlobalScope($this)
                ->getQuery()
                ->orderBy($column, $direction)
            ;

            return $builder;
        });
    }
}
