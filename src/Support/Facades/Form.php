<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

class Form extends Facade
{
    /**
     * Get the registered name of the component.
     * 
     * Resolves to:
     * - October\Rain\Html\FormBuilder
     * 
     * @return string
     */
    protected static function getFacadeAccessor() { return 'form'; }
}
