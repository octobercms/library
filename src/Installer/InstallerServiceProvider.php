<?php

namespace October\Rain\Installer;

use Event;
use System\Installer\Classes\InstallerEventHandler;
use October\Rain\Support\ServiceProvider;

/**
 * InstallProvider
 */
class InstallerServiceProvider extends ServiceProvider
{
    /**
     * @var string WANT_VERSION is the default composer version string to use.
     */
    const WANT_VERSION = '^4.0';

    /**
     * register the service provider.
     */
    public function register()
    {
        $this->app->singleton('core.installer', \October\Rain\Installer\InstallManager::class);
        $this->registerConsoleCommand('october.build', \October\Rain\Installer\Console\OctoberBuild::class);
        $this->registerConsoleCommand('october.install', \October\Rain\Installer\Console\OctoberInstall::class);
    }

    /**
     * boot the module events.
     */
    public function boot()
    {
    }

    /**
     * registerConsoleCommand registers a new console (artisan) command
     */
    protected function registerConsoleCommand(string $key, string $class)
    {
        $key = 'command.'.$key;

        $this->app->singleton($key, function ($app) use ($class) {
            return $this->app->make($class);
        });

        $this->commands($key);
    }
}
