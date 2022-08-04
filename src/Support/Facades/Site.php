<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * Site facade
 *
 * @method static array listSites()
 * @method static bool hasAnySite()
 * @method static bool hasMultiSite()
 * @method static int|null getSiteIdFromContext()
 *
 * @see \System\Classes\SiteManager
 */
class Site extends Facade
{
    /**
     * getFacadeAccessor gets the registered name of the component.
     */
    protected static function getFacadeAccessor()
    {
        return 'site.manager';
    }
}
