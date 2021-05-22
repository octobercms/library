<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * Flash
 *
 * @see \October\Rain\Support\FlashBag
 */
class Flash extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'flash';
    }
}
