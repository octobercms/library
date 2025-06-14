<?php

namespace October\Rain\Foundation\Providers;

use October\Rain\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

/**
 * AppServiceProvider contains providers for running October CMS
 *
 * @package october\foundation
 * @author Alexey Bobkov, Samuel Georges
 */
class AppServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * register the service provider.
     */
    public function register()
    {
        $this->app->singleton('october.installer', \October\Rain\Installer\InstallManager::class);
        $this->registerConsoleCommand('october.build', \October\Rain\Installer\Console\OctoberBuild::class);
        $this->registerConsoleCommand('october.install', \October\Rain\Installer\Console\OctoberInstall::class);
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

    /**
     * provides the returned services.
     * @return array
     */
    public function provides()
    {
        return [
            'october.installer',
            'command.october.build',
            'command.october.install',
        ];
    }
}
