<?php namespace October\Rain\Parse;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

/**
 * ParseServiceProvider
 */
class ParseServiceProvider extends ServiceProvider implements DeferrableProvider
{
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
     * provides the returned services.
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
