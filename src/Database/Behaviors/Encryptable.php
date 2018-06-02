<?php namespace October\Rain\Database\Behaviors;

use Exception;
use \October\Rain\Database\ModelTraitBehavior;

/**
 * Encryptable trait as behaviour
 *
 * @package october\database
 * @author JoakimBo
 */
class Encryptable extends ModelTraitBehavior
{
    use \October\Rain\Database\Traits\Encryptable;

    public function bootEncryptable()
    {
        if (!$this->model->propertyExists('encryptable'))
        {
            throw new Exception(sprintf(
                'You must define a $encryptable property in %s to use the Encryptable behaviour.', get_class($this->model)
            ));
        }
        /*
         * Encrypt required fields when necessary
         */
        $model = $this->model;
        $encryptable = $this->getEncryptableAttributes();

        $model->bindEvent('model.beforeSetAttribute', function($key, $value) use ($model, $encryptable)
        {
            if (in_array($key, $encryptable) && !empty($value))
            {
                return $model->makeEncryptableValue($key, $value);
            }
        });

        $model->bindEvent('model.beforeGetAttribute', function($key) use ($model, $encryptable)
        {
            if (in_array($key, $encryptable) && array_get($model->attributes, $key) != null)
            {
                return $model->getEncryptableValue($key);
            }
        });
    }
}
