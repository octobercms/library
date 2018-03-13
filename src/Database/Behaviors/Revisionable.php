<?php namespace October\Rain\Database\Behaviors;

use Exception;
use \October\Rain\Database\ModelTraitBehavior;

/**
 * Revisionable trait as behaviour
 *
 * @package october\database
 * @author JoakimBo
 */
class Revisionable extends ModelTraitBehavior
{
    use \October\Rain\Database\Traits\Revisionable;

    public function bootRevisionable()
    {
        if (!$this->model->propertyExists('revisionable'))
        {
            throw new Exception(sprintf(
                'You must define a $revisionable property in %s to use the Revisionable trait.', get_class($this->model);
            ));
        }

        $model = $this->model;

        $model->bindEvent('model.afterUpdate', function() use ($model) {
            $model->revisionableAfterUpdate();
        });

        $model->bindEvent('model.afterDelete', function() use ($model) {
            $model->revisionableAfterDelete();
        });
    }
}
