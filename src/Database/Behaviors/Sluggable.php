<?php namespace October\Rain\Database\Behaviors;

use \October\Rain\Database\ModelTraitBehavior;

/**
 * Sluggable trait as behaviour
 *
 * @package october\database
 * @author JoakimBo
 */
class Sluggable extends ModelTraitBehavior
{
    use \October\Rain\Database\Traits\Sluggable;

    public static function bootSluggable()
    {
        if (!$this->model->propertyExists('slugs'))
        {
            throw new Exception(sprintf(
                'You must define a $slugs property in %s to use the Sluggable trait.', get_class($this->model)
            ));
        }
        /*
         * Set slugged attributes on new records
         */
        $model = $this->model;

        $model->bindEvent('model.saveInternal', function() use ($model)
        {
            if ($model->exists)
            {
                return;
            }

            $model->slugAttributes();
        });
    }
}
