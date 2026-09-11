<?php namespace October\Rain\Foundation\Console;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Foundation\Console\RouteCacheCommand as RouteCacheCommandBase;

class RouteCacheCommand extends RouteCacheCommandBase
{
    /**
     * Boot a fresh copy of the application and get the routes.
     *
     * @return \Illuminate\Routing\RouteCollection
     */
    protected function getFreshApplicationRoutes()
    {
        $app = $this->getFreshApplication();

        // Late routes resolve the router via facades so point them at the fresh app
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);
        Container::setInstance($app);

        try {
            // Register the late routes
            $routes = $app->make('router')->registerLateRoutes()->getRoutes();
        }
        finally {
            // Restore the original app to preserve the Laravel post-bootstrap flow
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication($this->laravel);
            Container::setInstance($this->laravel);
        }

        return tap($routes, function ($routes) {
            $routes->refreshNameLookups();
            $routes->refreshActionLookups();
        });
    }
}
