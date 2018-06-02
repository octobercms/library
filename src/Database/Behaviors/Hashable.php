<?php namespace October\Rain\Database\Behaviors;

use Exception;
use \October\Rain\Database\ModelTraitBehavior;

/**
 * Hashable trait as behaviour
 *
 * @package october\database
 * @author JoakimBo
 */
class Hashable extends ModelTraitBehavior
{
    use \October\Rain\Database\Traits\Hashable;

    public function bootHashable()
    {
        if (!$this->model->propertyExists('hashable'))
        {
            throw new Exception(sprintf(
                'You must define a $hashable property in %s to use the Hashable behaviour.', get_class($this->model)
            ));
        }
        /*
         * Hash required fields when necessary
         */
        $model = $this->model;
        $hashable = $this->getHashableAttributes();

        $model->bindEvent('model.beforeSetAttribute', function($key, $value) use ($model, $hashable)
        {
            if (in_array($key, $hashable) && !empty($value))
            {
                return $model->makeHashValue($key, $value);
            }
        });
    }
}
