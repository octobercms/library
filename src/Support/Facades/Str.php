<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * @see \October\Rain\Support\Str
 */
class Str extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'string';
    }
}
