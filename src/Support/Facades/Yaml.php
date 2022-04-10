<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * Yaml
 *
 * @method static string parse(string $contents)
 * @method static string parseFile(string $fileName)
 * @method static string parseFileCached(string $fileName)
 * @method static string render(array $vars, array $options = [])
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
