<?php namespace October\Rain\Router;

use Illuminate\Routing\RoutingServiceProvider as RoutingServiceProviderBase;

/**
 * RoutingServiceProvider
 *
 * @package october\router
 * @author Alexey Bobkov, Samuel Georges
 */
class RoutingServiceProvider extends RoutingServiceProviderBase
{
    /**
     * boot any application services.
     */
    public function boot()
    {
        if ($this->app->routesAreCached()) {
            $this->loadCachedRoutes();
        }
        else {
            $this->app->booted(function () {
                $this->app['router']->getRoutes()->refreshNameLookups();
                $this->app['router']->getRoutes()->refreshActionLookups();
            });
        }
    }

    /**
     * loadCachedRoutes for the application.
     */
    protected function loadCachedRoutes()
    {
        $this->app->booted(function () {
            require $this->app->getCachedRoutesPath();
        });
    }

    /**
     * registerRouter instance.
     */
    protected function registerRouter()
    {
        $this->app->singleton('router', function ($app) {
            return new CoreRouter($app['events'], $app);
        });
    }

    /**
     * registerRedirector
     */
    protected function registerRedirector()
    {
        $this->app->singleton('redirect', function ($app) {
            $redirector = new CoreRedirector($app['url']);

            // If the session is set on the application instance, we'll inject it into
            // the redirector instance. This allows the redirect responses to allow
            // for the quite convenient "with" methods that flash to the session.
            if (isset($app['session.store'])) {
                $redirector->setSession($app['session.store']);
            }

            return $redirector;
        });
    }
}
