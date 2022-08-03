<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * Ini
 *
 * @method static array parse(string $contents)
 * @method static array parseFile(string $fileName)
 * @method static string render(array $vars = [], int $level = 1)
 *
 * @see \October\Rain\Parse\Ini
 */
class Ini extends Facade
{
    /**
     * getFacadeAccessor returns the registered name of the component
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'parse.ini';
    }
}
