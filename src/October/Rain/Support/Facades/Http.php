<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * Network Http Facade
 */
class Http extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'network.http';
    }
}
