<?php namespace October\Rain\Foundation\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

/**
 * Kernel
 *
 * @package october\foundation
 * @author Alexey Bobkov, Samuel Georges
 */
class Kernel extends HttpKernel
{
    /**
     * The bootstrap classes for the application.
     *
     * @var array
     */
    protected $bootstrappers = [
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \October\Rain\Foundation\Bootstrap\LoadConfiguration::class,
        \October\Rain\Foundation\Bootstrap\HandleExceptions::class,
        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
        \October\Rain\Foundation\Bootstrap\RegisterOctober::class,
        \October\Rain\Foundation\Bootstrap\RegisterProviders::class,
        \Illuminate\Foundation\Bootstrap\BootProviders::class,
    ];

    /**
     * @var array middleware is the application's global HTTP middleware stack.
     */
    protected $middleware = [];

    /**
     * @var array routeMiddleware is the application's route middleware.
     */
    protected $routeMiddleware = [];

    /**
     * @var array middlewareGroups is the application's route middleware groups.
     */
    protected $middlewareGroups = [];

    /**
     * @var array middlewarePriority is the priority-sorted list of middleware.
     *
     * Forces the listed middleware to always be in the given order.
     */
    protected $middlewarePriority = [
        \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
        \October\Rain\Foundation\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
        \Illuminate\Routing\Middleware\ThrottleRequests::class,
        \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
        \Illuminate\Contracts\Session\Middleware\AuthenticatesSessions::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ];
}
