<?php

namespace October\Rain\Install;

use Event;
use System\Installer\Classes\InstallerEventHandler;
use October\Rain\Support\ServiceProvider;

/**
 * InstallProvider
 */
class InstallProvider extends ServiceProvider
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
        $this->app->singleton('core.installer', \System\Installer\Classes\InstallerManager::class);
        $this->registerConsoleCommand('october.build', \System\Installer\Console\OctoberBuild::class);
        $this->registerConsoleCommand('october.install', \System\Installer\Console\OctoberInstall::class);
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
