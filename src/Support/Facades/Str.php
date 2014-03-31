<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

class Str extends Facade
{
    /**
     * Get the registered name of the component.
     * 
     * Resolves to:
     * - October\Rain\Support\Str
     * 
     * @return string
     */
    protected static function getFacadeAccessor() { return 'string'; }
}
