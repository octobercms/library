<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * Network Http Facade
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class Http extends Facade
{
    /**
     * Get the registered name of the component.
     * 
     * Resolves to:
     * - October\Rain\Network\Http
     * 
     * @return string
     */
    protected static function getFacadeAccessor() { return 'network.http'; }
}
