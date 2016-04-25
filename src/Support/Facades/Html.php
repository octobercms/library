<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

class Html extends Facade
{
    /**
     * Get the registered name of the component.
     * 
     * Resolves to:
     * - October\Rain\Html\HtmlBuilder
     * 
     * @return string
     */
    protected static function getFacadeAccessor() { return 'html'; }
}
