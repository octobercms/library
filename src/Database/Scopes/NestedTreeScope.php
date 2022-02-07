<?php namespace October\Rain\Database\Scopes;

use Illuminate\Database\Eloquent\Model as ModelBase;
use Illuminate\Database\Eloquent\Scope as ScopeInterface;
use Illuminate\Database\Eloquent\Builder as BuilderBase;

/**
 * NestedTreeScope
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class NestedTreeScope implements ScopeInterface
{
    /**
     * apply the scope to a given Eloquent query builder.
     */
    public function apply(BuilderBase $builder, ModelBase $model)
    {
        $builder->orderBy($model->getLeftColumnName());
    }
}
