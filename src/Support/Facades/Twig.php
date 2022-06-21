<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * Twig
 *
 * @method static string parse(string $contents, array $vars = [])
 *
 * @see \October\Rain\Parse\Twig
 */
class Twig extends Facade
{
    /**
     * getFacadeAccessor returns the registered name of the component
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'parse.twig';
    }
}
