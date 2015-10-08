<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

class Markdown extends Facade
{
    /**
     * Get the registered name of the component.
     * 
     * Resolves to:
     * - October\Rain\Parse\Markdown
     * 
     * @return string
     */
    protected static function getFacadeAccessor() { return 'parse.markdown'; }
}
