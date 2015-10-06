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

        $this->app['twig'] = $this->app->share(function($app) {
            return new Twig;
        });

        $this->app['ini'] = $this->app->share(function($app) {
            return new Ini;
        });

        // $this->app['twig.environment'] = $this->app->share(function($app) {
        //     $emptyLoader = new \Twig_Loader_Array([]);
        //     return new \Twig_Environment($emptyLoader);
        // });
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return array('markdown', 'yaml', 'twig', 'ini');
    }
}
