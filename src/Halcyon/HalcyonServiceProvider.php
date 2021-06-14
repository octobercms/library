<?php namespace October\Rain\Halcyon;

use October\Rain\Halcyon\Datasource\Resolver;
use October\Rain\Support\ServiceProvider;

/**
 * HalcyonServiceProvider
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class HalcyonServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        Model::setDatasourceResolver($this->app['halcyon']);

        Model::setEventDispatcher($this->app['events']);

        Model::setCacheManager($this->app['cache']);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        Model::clearBootedModels();

        Model::clearExtendedClasses();

        Model::flushEventListeners();

        $this->app->singleton('halcyon', function ($app) {
            return new Resolver;
        });
    }
}
