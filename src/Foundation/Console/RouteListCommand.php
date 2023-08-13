<?php namespace October\Rain\Foundation\Console;

use Illuminate\Foundation\Console\RouteListCommand as RouteListCommandBase;
use Illuminate\Routing\Router;

/**
 * RouteListCommand
 */
class RouteListCommand extends RouteListCommandBase
{
    /**
     * __construct a new route command instance.
     *
     * @param \Illuminate\Routing\Router $router
     * @return void
     */
    public function __construct(Router $router)
    {
        if ($router instanceof \October\Rain\Router\CoreRouter) {
            $router->registerLateRoutes();
        }

        parent::__construct($router);
    }
}
