<?php namespace October\Rain\Foundation\Console;

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
        $routes = $this->getFreshApplication()['router']->registerLateRoutes();

        return tap($routes->getRoutes(), function ($routes) {
            $routes->refreshNameLookups();
            $routes->refreshActionLookups();
        });
    }
}
