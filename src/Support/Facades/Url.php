<?php namespace October\Rain\Support\Facades;

use Illuminate\Support\Facades\URL as UrlBase;

/**
 * Url
 *
 * @method static string assetVersion(string $path)
 *
 * @see \Illuminate\Routing\UrlGenerator
 * @see \October\Rain\Html\UrlMixin
 */
class Url extends UrlBase
{
    /**
     * getFacadeAccessor returns the registered name of the component
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'url';
    }
}
