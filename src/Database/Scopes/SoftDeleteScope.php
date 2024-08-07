<?php namespace October\Rain\Database\Scopes;

use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model as ModelBase;
use Illuminate\Database\Eloquent\Builder as BuilderBase;

/**
 * SoftDeleteScope
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class SoftDeleteScope extends SoftDeletingScope
{
    /**
     * apply the scope to a given Eloquent query builder.
     */
    public function apply(BuilderBase $builder, ModelBase $model)
    {
        if ($model->isSoftDeleteEnabled()) {
            $builder->whereNull($model->getQualifiedDeletedAtColumn());
        }
    }
}
