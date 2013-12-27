<?php namespace October\Rain\Support;

use Illuminate\Support\Facades\Facade as FacadeParent;

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