<?php namespace October\Rain\Parse;

use Illuminate\Support\ServiceProvider;

class ParseServiceProvider extends ServiceProvider
{
    /**
     * @var bool defer indicates if loading of the provider is deferred
     */
    protected $defer = true;

    /**
     * register the service provider.
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
     * provides gets the services provided by the provider
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
