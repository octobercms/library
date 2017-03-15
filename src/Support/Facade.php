<?php namespace October\Rain\Support;

use Illuminate\Support\Facades\Facade as FacadeParent;

/**
 * Facade base class
 * Adds the ability to define a fallback instance.
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class Facade extends FacadeParent
{

    /**
     * @inheritDoc
     */
    protected static function resolveFacadeInstance($name)
    {
        if (
            !is_object($name) &&
            !static::$app->bound($name) &&
            ($instance = static::getFacadeInstance()) !== null
        ) {
            static::$app->instance($name, $instance);
        }

        return parent::resolveFacadeInstance($name);
    }

    /**
     * If the accessor is not found via getFacadeAccessor, use this instance as a fallback.
     *
     * @return mixed
     */
    protected static function getFacadeInstance()
    {
        return null;
    }

}