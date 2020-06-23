<?php namespace October\Rain\Config;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;

class ConfigServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->app->singleton('config', function ($app) {
            return new Repository($this->getConfigLoader(), $app['env']);
        });
    }

    /**
     * Get the services provided by the provider.
     * @return array
     */
    public function provides()
    {
        return ['config'];
    }

    /**
     * Get the configuration loader instance.
     *
     * @return \October\Rain\Config\LoaderInterface
     */
    public function getConfigLoader()
    {
        return new FileLoader(new Filesystem, $this->app['path.config']);
    }
}
