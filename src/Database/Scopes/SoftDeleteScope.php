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

    /**
     * extend the query builder with a delete that follows the soft delete toggle.
     */
    public function extend(BuilderBase $builder)
    {
        parent::extend($builder);

        $builder->onDelete(function (BuilderBase $builder) {
            $model = $builder->getModel();

            if (!$model->isSoftDeleteEnabled()) {
                return $builder->toBase()->delete();
            }

            return $builder->update([
                $this->getDeletedAtColumn($builder) => $model->freshTimestampString(),
            ]);
        });
    }
}
