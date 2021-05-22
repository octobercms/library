<?php namespace October\Rain\Argon;

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
     * @var bool defer indicates if loading of the provider is deferred
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $locale = $this->app['translator']->getLocale();

        $this->setArgonLocale($locale);

        $this->app['events']->listen('locale.changed', function ($locale) {
            $this->setArgonLocale($locale);
        });
    }

    /**
     * Sets the locale using the correct load order.
     */
    protected function setArgonLocale($locale)
    {
        Argon::setFallbackLocale($this->getFallbackLocale($locale));
        Argon::setLocale($locale);
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

    /**
     * register the service provider.
     */
    public function register()
    {
    }

    /**
     * provides gets the services provided by the provider
     */
    public function provides()
    {
        return ['Date'];
    }
}
