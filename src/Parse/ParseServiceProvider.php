<?php namespace October\Rain\Parse;

use Illuminate\Support\ServiceProvider;

class ParseServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->app['markdown'] = $this->app->share(function($app) {
            return new Markdown;
        });

        $this->app['yaml'] = $this->app->share(function($app) {
            return new Yaml;
        });
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return array('markdown', 'yaml');
    }
}
