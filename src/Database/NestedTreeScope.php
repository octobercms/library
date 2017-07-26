<?php namespace October\Rain\Database;

use Illuminate\Database\Eloquent\Model as ModelBase;
use Illuminate\Database\Eloquent\Scope as ScopeInterface;
use Illuminate\Database\Eloquent\Builder as BuilderBase;

class NestedTreeScope implements ScopeInterface
{
    /**
     * Apply the scope to a given Eloquent query builder.
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(BuilderBase $builder, ModelBase $model)
    {
        $builder->orderBy($model->getLeftColumnName());
    }
}
