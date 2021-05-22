<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * Html
 *
 * @see \October\Rain\Html\HtmlBuilder
 */
class Html extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'html';
    }
}
