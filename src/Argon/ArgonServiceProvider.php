<?php namespace October\Rain\Argon;

use October\Rain\Support\ServiceProvider;

class ArgonServiceProvider extends ServiceProvider
{
    /**
     * @var bool Indicates if loading of the provider is deferred.
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
        Argon::setLocale($locale);

        $fallbackLocale = $this->getFallbackLocale($locale);
        if ($locale !== $fallbackLocale) {
            Argon::setFallbackLocale($fallbackLocale);
        }
    }

    /**
     * Get the locale to use as the fallback
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
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Date'];
    }
}
