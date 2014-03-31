<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

class Flash extends Facade
{
    /**
     * Get the registered name of the component.
     * 
     * Resolves to:
     * - October\Rain\Support\FlashBag
     * 
     * @return string
     */
    protected static function getFacadeAccessor() { return 'flash'; }
}
