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
 * @method static array listSiteIdsInGroup($siteId)
 * @method static array listSiteIdsInLocale($siteId)
 * @method static iterable listSites()
 * @method static int|null getSiteIdFromContext()
 * @method static string|null getSiteCodeFromContext()
 * @method static mixed getSiteFromContext()
 * @method static bool hasGlobalContext()
 * @method static void withGlobalContext(callable $callback)
 * @method static void withContext($siteId, callable $callback)
 * @method static bool hasFeature(string $name)
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
        return 'system.sites';
    }
}
