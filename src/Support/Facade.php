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

    /**
     * @var boolean Is the singleton registered in IoC?
     */
    protected static $registeredSingletons = [];

    public static function registerSingletonInstance()
    {
        $instanceClass = static::getFacadeAccessor();
        if (isset(static::$registeredSingletons[$instanceClass]) || !method_exists($instanceClass, 'instance'))
            return;

        static::$app->instance($instanceClass, $instanceClass::instance());
        static::$registeredSingletons[$instanceClass] = true;
    }

}