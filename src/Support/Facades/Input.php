<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * Input
 *
 * @see Illuminate\Http\Request
 */
class Input extends Facade
{
    /**
     * get an item from the input data
     * This method is used for all request verbs (GET, POST, PUT, and DELETE)
     *
     * @param  string|null  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function get($key = null, $default = null)
    {
        return static::$app['request']->input($key, $default);
    }

    /**
     * getFacadeAccessor returns the registered name of the component
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'request';
    }
}
