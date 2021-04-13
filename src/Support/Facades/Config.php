<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * @see \October\Rain\Config\Repository
 */
class Config extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'config';
    }
}
