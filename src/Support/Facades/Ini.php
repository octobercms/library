<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * Ini
 *
 * @see \October\Rain\Parse\Ini
 */
class Ini extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'parse.ini';
    }
}
