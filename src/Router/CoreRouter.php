<?php namespace October\Rain\Router;

use Illuminate\Http\Request;
use Illuminate\Routing\Router as RouterBase;

/**
 * CoreRouter adds extra events to the base router and ensures late routes
 * are registered with the caching system.
 *
 * @package october\router
 * @author Alexey Bobkov, Samuel Georges
 */
class CoreRouter extends RouterBase
{
    /**
     * @var bool routerEventsBooted
     */
    protected $routerEventsBooted = false;

    /**
     * dispatch the request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function dispatch(Request $request)
    {
        $this->currentRequest = $request;

        $this->events->dispatch('router.before', [$request]);

        $response = $this->dispatchToRoute($request);

        $this->events->dispatch('router.after', [$request, $response]);

        return $response;
    }

    /**
     * before is a new filter registered with the router.
     *
     * @param  string|callable  $callback
     * @return void
     */
    public function before($callback)
    {
        $this->events->listen('router.before', $callback);
    }

    /**
     * after is a new filter registered with the router.
     *
     * @param  string|callable  $callback
     * @return void
     */
    public function after($callback)
    {
        $this->events->listen('router.after', $callback);
    }

    /**
     * registerLateRoutes found within "before" filter, some are registered here.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function registerLateRoutes()
    {
        if (!$this->routerEventsBooted) {
            $this->events->dispatch('router.before', [new Request]);
        }

        $this->routerEventsBooted = true;

        return $this;
    }
}
