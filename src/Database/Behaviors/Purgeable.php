<?php namespace October\Rain\Database\Behaviors;

use Exception;
use \October\Rain\Database\ModelTraitBehavior;

/**
 * Purgeable trait as behaviour
 *
 * @package october\database
 * @author JoakimBo
 */
class Purgeable extends ModelTraitBehavior
{
    use \October\Rain\Database\Traits\Purgeable;

    public function bootPurgeable()
    {
        if (!isset($this->model->purgeable))
            throw new Exception(sprintf(
                'You must define a $purgeable property in %s to use the Purgeable trait.', get_class($this->model)
            ));
        /*
         * Remove any purge attributes from the data set
         */
        $model_class = get_class($this->model);
        $model_class::extend(function($model){
            $model->bindEvent('model.saveInternal', function() use ($model) {
                $model->purgeAttributes();
            });
        });
    }
}
