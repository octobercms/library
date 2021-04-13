<?php namespace October\Rain\Foundation\Console;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Console\RouteListCommand as RouteListCommandBase;

class RouteListCommand extends RouteListCommandBase
{
    /**
     * Create a new route command instance.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function __construct(Router $router)
    {
        $router->registerLateRoutes();

        parent::__construct($router);
    }
}
