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
        $this->app->singleton('parse.markdown', function ($app) {
            return new Markdown;
        });

        $this->app->singleton('parse.yaml', function ($app) {
            return new Yaml;
        });

        $this->app->singleton('parse.twig', function ($app) {
            return new Twig;
        });

        $this->app->singleton('parse.ini', function ($app) {
            return new Ini;
        });
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return [
            'parse.markdown',
            'parse.yaml',
            'parse.twig',
            'parse.ini'
        ];
    }
}
