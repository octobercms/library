<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

class Twig extends Facade
{
    /**
     * Get the registered name of the component.
     * 
     * Resolves to:
     * - October\Rain\Parse\Twig
     * 
     * @return string
     */
    protected static function getFacadeAccessor() { return 'parse.twig'; }
}
