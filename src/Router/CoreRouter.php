<?php namespace October\Rain\Router;

use Illuminate\Http\Request;
use Illuminate\Routing\Router as RouterBase;

/**
 * CoreRouter
 *
 * @package october\router
 * @author Alexey Bobkov, Samuel Georges
 */
class CoreRouter extends RouterBase
{
    /**
     * @var bool Router registered
     */
    protected $routerEventsBooted = false;

    /**
     * Dispatch the request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function dispatch(Request $request)
    {
        $this->currentRequest = $request;

        $this->events->fire('router.before', [$request]);

        $response = $this->dispatchToRoute($request);

        $this->events->fire('router.after', [$request, $response]);

        return $response;
    }

    /**
     * Register a new "before" filter with the router.
     *
     * @param  string|callable  $callback
     * @return void
     */
    public function before($callback)
    {
        $this->events->listen('router.before', $callback);
    }

    /**
     * Register a new "after" filter with the router.
     *
     * @param  string|callable  $callback
     * @return void
     */
    public function after($callback)
    {
        $this->events->listen('router.after', $callback);
    }

    /**
     * Register routers found within "before" filter, some are registered here.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function registerLateRoutes()
    {
        if (!$this->routerEventsBooted) {
            $this->events->fire('router.before', [new Request]);
        }

        $this->routerEventsBooted = true;

        return $this;
    }
}
