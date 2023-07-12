<?php namespace October\Rain\Support\Facades;

use Illuminate\Support\Facades\URL as UrlBase;

/**
 * Url
 *
 * @see \Illuminate\Routing\UrlGenerator
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
