<?php namespace October\Rain\Support;

use October\Rain\Support\Facade;

/**
 * Markdown parser Facade
 *
 * @package october\support
 * @author Frank Wikström
 */
class Markdown extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * Resolves to:
     * - October\Rain\Support\Markdown
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'markdown'; }
}
