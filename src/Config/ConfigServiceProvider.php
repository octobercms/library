<?php namespace October\Rain\Config;

use Illuminate\Support\ServiceProvider;
use October\Rain\Filesystem\Filesystem;

/**
 * ConfigServiceProvider
 */
class ConfigServiceProvider extends ServiceProvider
{
    /**
     * @var bool defer indicates if loading of the provider is deferred
     */
    protected $defer = false;

    /**
     * register the service provider.
     */
    public function register()
    {
        $this->app->singleton('config', function ($app) {
            return new Repository($this->getConfigLoader(), $app['env']);
        });
    }

    /**
     * provides gets the services provided by the provider
     */
    public function provides()
    {
        return ['config'];
    }

    /**
     * getConfigLoader instance
     * @return \October\Rain\Config\LoaderInterface
     */
    public function getConfigLoader()
    {
        return new FileLoader(new Filesystem, $this->app['path'].'/config');
    }
}
