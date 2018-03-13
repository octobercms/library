<?php namespace October\Rain\Database\Behaviors;

use Exception;
use \October\Rain\Database\ModelTraitBehavior;

/**
 * Validation trait as behaviour
 *
 * @package october\database
 * @author JoakimBo
 */
class Validation extends ModelTraitBehavior
{
    use \October\Rain\Database\Traits\Validation;

    public function bootValidation()
    {
        if (!$this->model->propertyExists('rules'))
        {
            throw new Exception(sprintf(
                'You must define a $rules property in %s to use the Validation trait.', get_class($this->model)
            ));
        }

        $model = $this->model;

        $model->bindEvent('model.saveInternal', function($data, $options) use ($model)
        {
            /*
             * If forcing the save event, the beforeValidate/afterValidate
             * events should still fire for consistency. So validate an
             * empty set of rules and messages.
             */
            $force = array_get($options, 'force', false);
            if ($force) {
                $valid = $model->validate([], []);
            }
            else {
                $valid = $model->validate();
            }
            if (!$valid) {
                return false;
            }
        }, 500);
    }
}
