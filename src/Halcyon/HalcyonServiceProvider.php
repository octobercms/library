<?php namespace October\Rain\Halcyon;

use October\Rain\Halcyon\Model;
use October\Rain\Halcyon\Datasource\Resolver;
use October\Rain\Support\ServiceProvider;

/**
 * Service provider
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
        // The halcyon resolver is used to resolve various datasources,
        // since multiple datasources might be managed.
        $this->app->singleton('halcyon', function ($app) {
            return new Resolver;
        });
    }

}
