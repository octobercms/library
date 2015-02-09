<?php namespace October\Rain\Html;

use Illuminate\Support\ServiceProvider;

class UrlServiceProvider extends ServiceProvider
{

    /**
     * @var bool Indicates if loading of the provider is deferred.
     */
    protected $defer = false;

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->registerUrlGenerator();

        if (!$this->app->runningInConsole()) {
            $this->setUrlGeneratorPolicy();
        }
    }

    /**
     * Register the URL generator service.
     * @return void
     */
    protected function registerUrlGenerator()
    {
        $this->app['url'] = $this->app->share(function($app) {

            if (!isset($app['routes'])) {
                $routes = $app['router']->getRoutes();
                $app->instance('routes', $routes);
            }
            else {
                $routes = $app['routes'];
            }

            $request = $app->rebinding('request', $this->requestRebinder());

            $url = new UrlGenerator($routes, $request);

            $url->setSessionResolver(function() {
                return $this->app['session'];
            });

            // If the route collection is "rebound", for example, when the routes stay
            // cached for the application, we will need to rebind the routes on the
            // URL generator instance so it has the latest version of the routes.
            $app->rebinding('routes', function($app, $routes) {
                $app['url']->setRoutes($routes);
            });

            return $url;
        });
    }

    /**
     * Get the URL generator request rebinder.
     * @return \Closure
     */
    protected function requestRebinder()
    {
        return function($app, $request) {
            $app['url']->setRequest($request);
        };
    }

    /**
     * Controls how URL links are generated throughout the application.
     *
     * relative - relative to the application, schema and hostname is omitted
     * detect   - detect hostname and use the current schema
     * secure   - detect hostname and force HTTPS schema
     * insecure - detect hostname and force HTTP schema
     * force    - force hostname and schema using app.url config value
     */
    public function setUrlGeneratorPolicy()
    {
        $policy = $this->app['config']->get('cms.linkPolicy', 'relative');

        switch (strtolower($policy)) {
            case 'detect':
                // Do nothing
                break;
            case 'force':
                $appUrl = $this->app['config']->get('app.url');
                $this->app['url']->forceRootUrl($appUrl);
                break;
            case 'insecure':
                $this->app['url']->forceSchema('http');
                break;
            case 'secure':
                $this->app['url']->forceSchema('https');
                break;
            case 'relative':
                $this->app['url']->forceRelative();
                break;
        }
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return ['url'];
    }

}
