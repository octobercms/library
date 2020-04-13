<?php namespace October\Rain\Argon;

use October\Rain\Support\ServiceProvider;

class ArgonServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
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
            $resource = __DIR__ . '/../../../../jenssegers/date/src/Lang/'.$target.'.php';
            if (file_exists($resource)) {
                return $target;
            }
        }

        return $this->app['config']->get('app.fallback_locale');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }
}
