<?php namespace October\Rain\Router;

use Illuminate\Routing\RoutingServiceProvider as RoutingServiceProviderBase;

class RoutingServiceProvider extends RoutingServiceProviderBase
{
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
