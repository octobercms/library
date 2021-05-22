<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * Markdown
 *
 * @see \October\Rain\Parse\Markdown
 */
class Markdown extends Facade
{
    /**
     * Get the registered name of the component.
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'parse.markdown';
    }
}
