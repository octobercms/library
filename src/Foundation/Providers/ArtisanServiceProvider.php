<?php namespace October\Rain\Foundation\Providers;

use October\Rain\Foundation\Console\ServeCommand;
use October\Rain\Foundation\Console\RouteListCommand;
use October\Rain\Foundation\Console\RouteCacheCommand;
use October\Rain\Foundation\Console\ClearCompiledCommand;
use Illuminate\Foundation\Providers\ArtisanServiceProvider as ArtisanServiceProviderBase;

/**
 * ArtisanServiceProvider
 */
class ArtisanServiceProvider extends ArtisanServiceProviderBase
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'CacheClear' => 'command.cache.clear',
        'CacheForget' => 'command.cache.forget',
        'ClearCompiled' => 'command.clear-compiled',
        'ConfigCache' => 'command.config.cache',
        'ConfigClear' => 'command.config.clear',
        'DbWipe' => 'command.db.wipe',
        'Down' => 'command.down',
        'Environment' => 'command.environment',
        'KeyGenerate' => 'command.key.generate',
        'Optimize' => 'command.optimize',
        'PackageDiscover' => 'command.package.discover',
        'QueueFailed' => 'command.queue.failed',
        'QueueFlush' => 'command.queue.flush',
        'QueueForget' => 'command.queue.forget',
        'QueueListen' => 'command.queue.listen',
        'QueueRestart' => 'command.queue.restart',
        'QueueRetry' => 'command.queue.retry',
        'QueueWork' => 'command.queue.work',
        'RouteCache' => 'command.route.cache',
        'RouteClear' => 'command.route.clear',
        'RouteList' => 'command.route.list',
        'ScheduleFinish' => \Illuminate\Console\Scheduling\ScheduleFinishCommand::class,
        'ScheduleRun' => \Illuminate\Console\Scheduling\ScheduleRunCommand::class,
        'Seed' => 'command.seed',
        'StorageLink' => 'command.storage.link',
        'Up' => 'command.up',
        'ViewClear' => 'command.view.clear',
    ];

    /**
     * @var array devCommands to be registered.
     */
    protected $devCommands = [
        'Serve' => 'command.serve',
        'VendorPublish' => 'command.vendor.publish',
    ];

    /**
     * register the service provider
     */
    public function register()
    {
        parent::register();
    }

    /**
     * registerRouteCacheCommand
     */
    protected function registerRouteCacheCommand()
    {
        $this->app->singleton('command.route.cache', function ($app) {
            return new RouteCacheCommand($app['files']);
        });
    }

    /**
     * registerRouteListCommand
     */
    protected function registerRouteListCommand()
    {
        $this->app->singleton('command.route.list', function ($app) {
            return new RouteListCommand($app['router']);
        });
    }

    /**
     * registerServeCommand
     */
    protected function registerServeCommand()
    {
        $this->app->singleton('command.serve', function () {
            return new ServeCommand;
        });
    }

    /**
     * registerClearCompiledCommand
     */
    protected function registerClearCompiledCommand()
    {
        $this->app->singleton('command.clear-compiled', function () {
            return new ClearCompiledCommand;
        });
    }
}
