<?php namespace October\Rain\Database\Behaviors;

use Exception;
use \October\Rain\Database\ModelTraitBehavior;

/**
 * Nullable trait as behaviour
 *
 * @package october\database
 * @author JoakimBo
 */
class Nullable extends ModelTraitBehavior
{
    use \October\Rain\Database\Traits\Nullable;

    public function bootNullable()
    {
        if (!$this->model->propertyExists('nullable'))
        {
            throw new Exception(sprintf(
                'You must define a $nullable property in %s to use the Nullable trait.', get_class($this->model)
            ));
        }

        $model = $this->model;

        $model->bindEvent('model.beforeSave', function() use ($model)
        {
            $model->nullableBeforeSave();
        });
    }
}
