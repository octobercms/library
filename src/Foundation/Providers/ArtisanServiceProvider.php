<?php namespace October\Rain\Foundation\Providers;

use October\Rain\Foundation\Console\KeyGenerateCommand;
use October\Rain\Foundation\Console\ClearCompiledCommand;
use Illuminate\Foundation\Providers\ArtisanServiceProvider as ArtisanServiceProviderBase;

class ArtisanServiceProvider extends ArtisanServiceProviderBase
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'CacheClear'      => 'command.cache.clear',
        'CacheForget'     => 'command.cache.forget',
        'ClearCompiled'   => 'command.clear-compiled',
        'ConfigCache'     => 'command.config.cache',
        'ConfigClear'     => 'command.config.clear',
        'Down'            => 'command.down',
        'Environment'     => 'command.environment',
        'KeyGenerate'     => 'command.key.generate',
        'Optimize'        => 'command.optimize',
        'PackageDiscover' => 'command.package.discover',
        'QueueFailed'     => 'command.queue.failed',
        'QueueFlush'      => 'command.queue.flush',
        'QueueForget'     => 'command.queue.forget',
        'QueueListen'     => 'command.queue.listen',
        'QueueRestart'    => 'command.queue.restart',
        'QueueRetry'      => 'command.queue.retry',
        'QueueWork'       => 'command.queue.work',
        'RouteCache'      => 'command.route.cache',
        'RouteClear'      => 'command.route.clear',
        'RouteList'       => 'command.route.list',
        'ScheduleFinish'  => \Illuminate\Console\Scheduling\ScheduleFinishCommand::class,
        'ScheduleRun'     => \Illuminate\Console\Scheduling\ScheduleRunCommand::class,
        'Seed'            => 'command.seed',
        'StorageLink'     => 'command.storage.link',
        'Up'              => 'command.up',
        'ViewClear'       => 'command.view.clear',
    ];

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $devCommands = [
        'AppName'           => 'command.app.name',
        'Serve'             => 'command.serve',
        'VendorPublish'     => 'command.vendor.publish',
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerKeyGenerateCommand()
    {
        $this->app->singleton('command.key.generate', function ($app) {
            return new KeyGenerateCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerClearCompiledCommand()
    {
        $this->app->singleton('command.clear-compiled', function () {
            return new ClearCompiledCommand;
        });
    }
}
