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
     * boot any application services.
     */
    public function boot()
    {
        if ($this->app->routesAreCached()) {
            $this->loadCachedRoutes();
        }
        else {
            $this->app->booted(function () {
                $this->app['router']->getRoutes()->refreshNameLookups();
                $this->app['router']->getRoutes()->refreshActionLookups();
            });
        }
    }

    /**
     * loadCachedRoutes for the application.
     */
    protected function loadCachedRoutes()
    {
        $this->app->booted(function () {
            require $this->app->getCachedRoutesPath();
        });
    }

    /**
     * registerRouter instance.
     */
    protected function registerRouter()
    {
        $this->app->singleton('router', function ($app) {
            return new CoreRouter($app['events'], $app);
        });
    }
}
