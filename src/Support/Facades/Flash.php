<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

class Flash extends Facade
{
    /**
     * Get the class name this facade is acting on behalf of.
     * @return string
     */
    protected static function getFacadeAccessor() { return 'October\Rain\Support\FlashMessages'; }
}
