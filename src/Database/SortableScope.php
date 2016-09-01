<?php namespace October\Rain\Database;

use Illuminate\Database\Eloquent\ScopeInterface;
use Illuminate\Database\Eloquent\Model as ModelBase;
use Illuminate\Database\Eloquent\Builder as BuilderBase;

class SortableScope implements ScopeInterface
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(BuilderBase $builder, ModelBase $model)
    {
        $builder->getQuery()->orderBy($model->getSortOrderColumn());

        $builder->flag('sortable_scope_applied');

        $this->extend($builder);
    }

    /**
     * Remove the scope from the given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $builder
     * @param  \Illuminate\Database\Eloquent\Model $model
     *
     * @return void
     */
    public function remove(BuilderBase $builder, ModelBase $model)
    {
        $query = $builder->getQuery();
        $sortOrderColumn = $model->getSortOrderColumn();

        foreach ((array) $query->orders as $key => $order) {

            if ($order['column'] != $sortOrderColumn)
                continue;

            unset($query->orders[$key]);
            $query->orders = array_values($query->orders) ?: null;

            $builder->unflag('sortable_scope_applied');
        }
    }

    /**
     * Extend the Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    protected function extend(BuilderBase $builder)
    {
        $builder->macro('orderBy', function($builder, $column, $direction = 'asc') {

            if($builder->hasFlag('sortable_scope_applied')) {
                $this->remove($builder, $builder->getModel());
            }

            $builder->getQuery()->orderBy($column, $direction);

            return $builder;
        });
    }
}