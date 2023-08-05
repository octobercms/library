<?php namespace October\Rain\Argon;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Illuminate\Support\DateFactory;
use October\Rain\Support\ServiceProvider;

/**
 * ArgonServiceProvider
 *
 * @package october\argon
 * @author Alexey Bobkov, Samuel Georges
 */
class ArgonServiceProvider extends ServiceProvider
{
    /**
     * register the service provider.
     */
    public function register()
    {
        DateFactory::useClass(\October\Rain\Argon\Argon::class);
    }

    /**
     * boot the application events
     */
    public function boot()
    {
        $locale = $this->app['config']->get('app.locale');

        $this->setCarbonLocale($locale);

        $this->app['events']->listen('locale.changed', function ($locale) {
            $this->setCarbonLocale($locale);
        });
    }

    /**
     * setCarbonLocale sets the locale using the correct load order.
     */
    protected function setCarbonLocale($locale)
    {
        Carbon::setLocale($locale);
        CarbonImmutable::setLocale($locale);
        CarbonPeriod::setLocale($locale);
        CarbonInterval::setLocale($locale);

        $fallbackLocale = $this->getFallbackLocale($locale);
        if ($locale !== $fallbackLocale) {
            Carbon::setFallbackLocale($fallbackLocale);
        }
    }

    /**
     * Split the locale and use it as the fallback.
     */
    protected function getFallbackLocale($locale)
    {
        if ($position = strpos($locale, '-')) {
            $target = substr($locale, 0, $position);
            $resource = __DIR__ . '/../../../../nesbot/carbon/src/Carbon/Lang/'.$target.'.php';
            if (file_exists($resource)) {
                return $target;
            }
        }

        return $this->app['config']->get('app.fallback_locale');
    }
}
