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
     * @var bool scopeApplied
     */
    protected $scopeApplied;

    /**
     * apply the scope to a given Eloquent query builder.
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
     * extend the Eloquent query builder.
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
