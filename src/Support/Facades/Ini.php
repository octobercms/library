<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * @method static array parse(string $contents)
 * @method static array parseFile(string $fileName)
 * @method static array expandProperty(array &$array, string $key, $value)
 * @method static string render(array $vars = [], int $level = 1)
 *
 * @see \October\Rain\Parse\Ini
 */
class Ini extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'parse.ini';
    }
}
