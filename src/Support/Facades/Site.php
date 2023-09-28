<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * Site facade
 *
 * @method static mixed getSiteFromRequest(string $host, string $uri)
 * @method static mixed getSiteFromId($id)
 * @method static mixed getPrimarySite()
 * @method static bool hasAnySite()
 * @method static bool hasMultiSite()
 * @method static array listEnabled()
 * @method static array listSiteIds()
 * @method static array listSiteIdsInContext()
 * @method static array listSites()
 * @method static int|null getSiteIdFromContext()
 * @method static mixed getSiteFromContext()
 * @method static bool hasGlobalContext()
 * @method static void withGlobalContext(callable $callback)
 * @method static void withContext($siteId, callable $callback)
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
        // @deprecated use below
        // return 'system.sites';
        return 'site.manager';
    }
}
