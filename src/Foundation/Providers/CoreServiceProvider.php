<?php

namespace October\Rain\Foundation\Providers;

use October\Rain\Support\ServiceProvider;

/**
 * CoreServiceProvider contains providers for running October Rain
 *
 * @package october\foundation
 * @author Alexey Bobkov, Samuel Georges
 */
class CoreServiceProvider extends ServiceProvider
{
    /**
     * register the service provider.
     */
    public function register()
    {
        $this->app->register(\October\Rain\Events\EventServiceProvider::class);

        $this->app->register(\October\Rain\Parse\ParseServiceProvider::class);

        $this->app->singleton('files', \October\Rain\Filesystem\Filesystem::class);

        $this->app->singleton('core.composer', \October\Rain\Composer\ComposerManager::class);

        if (!$this->app instanceof \October\Rain\Foundation\Application) {
            $this->registerModelServices();
        }
    }

    /**
     * registerModelServices registers what Rain models need in a Laravel app specifically
     */
    protected function registerModelServices()
    {
        $this->app->register(\October\Rain\Filesystem\FilesystemServiceProvider::class);

        $this->app->singletonIf('db.dongle', function ($app) {
            $driver = $app['config']['database.connections.' . $app['db']->getDefaultConnection() . '.driver'];

            return new \October\Rain\Database\Dongle($driver, $app['db']);
        });
    }
}
