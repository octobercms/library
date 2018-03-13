<?php namespace October\Rain\Database\Behaviors;

use \October\Rain\Database\ModelTraitBehavior;

/**
 * SoftDelete trait as behaviour
 *
 * @package october\database
 * @author JoakimBo
 */
class SoftDelete extends ModelTraitBehavior
{
    use \October\Rain\Database\Traits\SoftDelete;

    public function bootSoftDelete()
    {
        $model = $this->model;

        $model->addGlobalScope(new SoftDeletingScope);

        $model->restoring(function($model) {
            $model->fireEvent('model.beforeRestore');
            if ($model->methodExists('beforeRestore')) {
                $model->beforeRestore();
            }
        });

        $model->restored(function($model) {
            $model->fireEvent('model.afterRestore');
            if ($model->methodExists('afterRestore')) {
                $model->afterRestore();
            }
        });
    }
}
