<?php namespace October\Rain\Halcyon;

use October\Rain\Halcyon\Model;
use October\Rain\Halcyon\Theme\ThemeResolver;
use October\Rain\Support\ServiceProvider;

class HalcyonServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        Model::setThemeResolver($this->app['halcyon']);

        Model::setEventDispatcher($this->app['events']);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // The halcyon resolver is used to resolve various themes,
        // since multiple themes might be managed.
        $this->app->singleton('halcyon', function ($app) {
            return new ThemeResolver;
        });
    }

}
