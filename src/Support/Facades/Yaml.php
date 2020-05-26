<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * @method static array parse(string $contents)
 * @method static array parseFile(string $fileName)
 * @method static string render(array $vars = [], array $options = [])
 *
 * @see \October\Rain\Parse\Yaml
 */
class Yaml extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'parse.yaml';
    }
}
