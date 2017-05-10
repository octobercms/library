<?php namespace October\Rain\Foundation\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{

    /**
     * The bootstrap classes for the application.
     *
     * @var array
     */
    protected $bootstrappers = [
        'October\Rain\Foundation\Bootstrap\RegisterClassLoader',
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        'October\Rain\Foundation\Bootstrap\LoadTranslation',
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
        'October\Rain\Foundation\Bootstrap\RegisterOctober',
        \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
        \Illuminate\Foundation\Bootstrap\BootProviders::class,
    ];

    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        'Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode',
        // 'Illuminate\Cookie\Middleware\EncryptCookies',
        // 'Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse',
        // 'Illuminate\Session\Middleware\StartSession',
        // 'Illuminate\View\Middleware\ShareErrorsFromSession',

        // 'App\Http\Middleware\VerifyCsrfToken',
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        // 'auth' => 'App\Http\Middleware\Authenticate',
        // 'auth.basic' => 'Illuminate\Auth\Middleware\AuthenticateWithBasicAuth',
        // 'guest' => 'App\Http\Middleware\RedirectIfAuthenticated',
    ];

    /**
     * The priority-sorted list of middleware.
     *
     * Forces the listed middleware to always be in the given order.
     *
     * @var array
     */
    protected $middlewarePriority = [
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Auth\Middleware\Authenticate::class,
        \Illuminate\Session\Middleware\AuthenticateSession::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ];


}