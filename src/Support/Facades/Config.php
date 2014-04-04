<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

class Config extends Facade
{
    /**
     * Get the registered name of the component.
     * 
     * Resolves to:
     * - October\Rain\Config\Repository
     * 
     * @return string
     */
    protected static function getFacadeAccessor() { return 'config'; }
}
