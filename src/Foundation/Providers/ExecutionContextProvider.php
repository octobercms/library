<?php namespace October\Rain\Foundation\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * ExecutionContextProvider sets the execution context globally
 */
class ExecutionContextProvider extends ServiceProvider
{
    /**
     * register the service provider.
     */
    public function register()
    {
        $this->app->scoped('execution.context', function ($app) {
            return $this->determineContext($app);
        });
    }

    /**
     * boot the service provider.
     */
    public function boot()
    {
        // Refresh execution context when Octane receives a new request
        if (class_exists(\Laravel\Octane\Events\RequestReceived::class)) {
            $this->app['events']->listen(\Laravel\Octane\Events\RequestReceived::class, function ($event) {
                $event->sandbox->forgetInstance('execution.context');
            });
        }
    }

    /**
     * determineContext evaluates the execution context from the current request.
     */
    protected function determineContext($app): string
    {
        $requestPath = $this->normalizeUrl($app['request']->path());

        $backendUri = $this->normalizeUrl($app['config']->get('backend.uri', 'backend'));

        if (str_starts_with($requestPath, $backendUri)) {
            return 'backend';
        }

        return 'frontend';
    }

    /**
     * normalizeUrl adds leading slash from a URL.
     *
     * @param string $url URL to normalize.
     * @return string Returns normalized URL.
     */
    protected function normalizeUrl($url)
    {
        if (substr($url, 0, 1) !== '/') {
            $url = '/'.$url;
        }

        if (!strlen($url)) {
            $url = '/';
        }

        return $url;
    }
}
