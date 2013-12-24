<?php namespace October\Rain\Support\Facades;

use Illuminate\Support\Facades\Facade;

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
