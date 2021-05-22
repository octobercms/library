<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * Yaml
 *
 * @see \October\Rain\Parse\Yaml
 */
class Yaml extends Facade
{
    /**
     * getFacadeAccessor returns the registered name of the component
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'parse.yaml';
    }
}
