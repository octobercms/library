<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

class Url extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * Resolves to:
     * - October\Rain\Router\UrlGenerator
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'url';
    }
}
