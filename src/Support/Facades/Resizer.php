<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * Resizer
 *
 * @see \October\Rain\Resize\Resizer
 */
class Resizer extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'resizer';
    }
}
