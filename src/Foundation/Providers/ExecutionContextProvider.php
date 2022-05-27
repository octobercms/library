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
        $this->app->singleton('execution.context', function ($app) {

            $requestPath = $this->normalizeUrl($app['request']->path());

            $backendUri = $this->normalizeUrl($app['config']->get('backend.uri', 'backend'));

            if (starts_with($requestPath, $backendUri)) {
                return 'backend';
            }

            return 'frontend';
        });
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
