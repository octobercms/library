<?php namespace October\Rain\Router;

use Illuminate\Routing\RoutingServiceProvider as RoutingServiceProviderBase;

/**
 * RoutingServiceProvider
 *
 * @package october\router
 * @author Alexey Bobkov, Samuel Georges
 */
class RoutingServiceProvider extends RoutingServiceProviderBase
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->routesAreCached()) {
            $this->loadCachedRoutes();
        }
    }

    /**
     * Load the cached routes for the application.
     *
     * @return void
     */
    protected function loadCachedRoutes()
    {
        $this->app->booted(function () {
            require $this->app->getCachedRoutesPath();
        });
    }

    /**
     * Register the router instance.
     *
     * @return void
     */
    protected function registerRouter()
    {
        $this->app->singleton('router', function ($app) {
            return new CoreRouter($app['events'], $app);
        });
    }
}
