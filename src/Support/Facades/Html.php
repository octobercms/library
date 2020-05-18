<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * @method static string entities(string $value)
 * @method static string decode(string $value)
 * @method static string script(string $url, array $attributes, bool $secure = null)
 * @method static string style(string $url, array $attributes = [], bool $secure = null)
 * @method static string image(string $url, string $alt = null, array $attributes = [], bool $secure = null)
 * @method static string link(string $url, string $title = null, array $attributes = [], bool $secure = null)
 * @method static string secureLink(string $url, string $title = null, array $attributes = [])
 * @method static string linkAsset(string $url, string $title = null, array $attributes = [], bool $secure = null)
 * @method static string linkSecureAsset(string $url, string $title = null, array $attributes = [])
 * @method static string linkRoute(string $name, string $title = null, array $parameters = [], array $attributes = [])
 * @method static string linkAction(string $action, string $title = null, array $parameters = [], array $attributes = [])
 * @method static string mailto(string $email, string $title = null, array $attributes = [])
 * @method static string email(string $email)
 * @method static string ol(array $list, array $attributes = [])
 * @method static string ul(array $list, array $attributes = [])
 * @method static string attributes(array $attributes)
 * @method static string obfuscate(string $value)
 * @method static string strip(string $string)
 * @method static string limit(string $html, int $maxLength = 100, string $end = '...')
 * @method static string clean(string $html)
 *
 * @see \October\Rain\Html\HtmlBuilder
 */
class Html extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'html';
    }
}
