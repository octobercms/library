<?php namespace October\Rain\Foundation\Providers;

use Illuminate\Support\ServiceProvider;

class ExecutionContextProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('execution.context', function ($app) {

            $requestPath = $this->normalizeUrl($app['request']->path());

            $backendUri = $this->normalizeUrl($app['config']->get('cms.backendUri', 'backend'));

            if (starts_with($requestPath, $backendUri)) {
                return 'back-end';
            }
            else {
                return 'front-end';
            }
        });
    }

    /**
     * Adds leading slash from a URL.
     *
     * @param string $url URL to normalize.
     * @return string Returns normalized URL.
     */
    protected function normalizeUrl($url)
    {
        if (substr($url, 0, 1) != '/') {
            $url = '/'.$url;
        }

        if (!strlen($url)) {
            $url = '/';
        }

        return $url;
    }
}
