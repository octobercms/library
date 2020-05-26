<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * @method static string current()
 * @method static string full()
 * @method static string previous($fallback = false)
 * @method static string to(string $path, $extra = [], bool $secure = null)
 * @method static string secure(string $path, array $parameters = [])
 * @method static string asset(string $path, bool $secure = null)
 * @method static string route(string $name, $parameters = [], bool $absolute = true)
 * @method static string action(string $action, $parameters = [], bool $absolute = true)
 * @method static \Illuminate\Contracts\Routing\UrlGenerator setRootControllerNamespace(string $rootNamespace)
 * @method static string buildUrl(array $url, array $replace = [], $flags = HTTP_URL_REPLACE, array &$newUrl = [])
 *
 * @see \October\Rain\Router\UrlGenerator
 */
class Url extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'url';
    }
}
