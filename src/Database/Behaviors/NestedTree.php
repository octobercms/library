<?php namespace October\Rain\Database\Behaviors;

use \October\Rain\Database\ModelTraitBehavior;

/**
 * NestedTree trait as behaviour
 *
 * @package october\database
 * @author JoakimBo
 */
class NestedTree extends ModelTraitBehavior
{
    use \October\Rain\Database\Traits\NestedTree;

    public function bootNestedTree()
    {
        $model = $this->model;

        $model->addGlobalScope(new NestedTreeScope);
        /*
         * Define relationships
         */
        $model->hasMany['children'] = [
            get_class($model),
            'key' => $model->getParentColumnName(),
            'order' => $model->getLeftColumnName()
        ];
        $model->belongsTo['parent'] = [
            get_class($model),
            'key' => $model->getParentColumnName()
        ];
        /*
         * Bind events
         */
        $model->bindEvent('model.beforeCreate', function() use ($model) {
            $model->setDefaultLeftAndRight();
        });
        $model->bindEvent('model.beforeSave', function() use ($model) {
            $model->storeNewParent();
        });
        $model->bindEvent('model.afterSave', function() use ($model) {
            $model->moveToNewParent();
            $model->setDepth();
        });
        $model->bindEvent('model.beforeDelete', function() use ($model) {
            $model->deleteDescendants();
        });
        if ($model->hasGlobalScope(SoftDeletingScope::class)) {
            $model->bindEvent('model.beforeRestore', function() use ($model) {
                $model->shiftSiblingsForRestore();
            });
            $model->bindEvent('model.afterRestore', function() use ($model) {
                $model->restoreDescendants();
            });
        }
    }
}
