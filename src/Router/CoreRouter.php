<?php namespace October\Rain\Router;

use Illuminate\Http\Request;
use Illuminate\Routing\Router as RouterBase;

class CoreRouter extends RouterBase
{
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
}
