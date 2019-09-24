<?php namespace October\Rain\Database;

use Illuminate\Database\Eloquent\Model as ModelBase;
use Illuminate\Database\Eloquent\Scope as ScopeInterface;
use Illuminate\Database\Eloquent\Builder as BuilderBase;

class SortableScope implements ScopeInterface
{
    protected $scopeApplied;

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(BuilderBase $builder, ModelBase $model)
    {
        $this->scopeApplied = true;

        $builder->getQuery()->orderBy($model->getSortOrderColumn());
    }

    /**
     * Extend the Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
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
