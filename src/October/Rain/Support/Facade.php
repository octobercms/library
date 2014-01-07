<?php namespace October\Rain\Support;

use Illuminate\Support\Facades\Facade as FacadeParent;

/**
 * Facade base class
 * Extension of illuminiate/support, automatically registered Singletons in IoC
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class Facade extends FacadeParent
{

    public static function registerSingletonInstance()
    {
        $instanceClass = static::getFacadeAccessor();
        if (!method_exists($instanceClass, 'instance'))
            return;

        static::$app->instance($instanceClass, $instanceClass::instance());
    }

}