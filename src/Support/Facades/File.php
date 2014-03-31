<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

class File extends Facade
{
    /**
     * Get the registered name of the component.
     * 
     * Resolves to:
     * - October\Rain\Filesystem\Filesystem
     * 
     * @return string
     */
    protected static function getFacadeAccessor() { return 'files'; }
}
