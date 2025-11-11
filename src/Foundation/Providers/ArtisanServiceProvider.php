<?php namespace October\Rain\Foundation\Providers;

use Illuminate\Console\Signals;
use October\Rain\Foundation\Console\ServeCommand;
use October\Rain\Foundation\Console\RouteListCommand;
use October\Rain\Foundation\Console\RouteCacheCommand;
use October\Rain\Foundation\Console\ProjectSetCommand;
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
    protected $commandsRain = [
        'RouteList' => RouteListCommand::class,
        'RouteCache' => RouteCacheCommand::class,
        'ProjectSet' => ProjectSetCommand::class,
        'ClearCompiled' => ClearCompiledCommand::class,
    ];

    /**
     * @var array devCommands to be registered.
     */
    protected $devCommandsRain = [
        'Serve' => ServeCommand::class,
    ];

    /**
     * register the service provider
     */
    public function register()
    {
        $this->registerCommands(array_merge(
            $this->commands,
            $this->commandsRain,
            $this->devCommands,
            $this->devCommandsRain
        ));

        Signals::resolveAvailabilityUsing(function () {
            return $this->app->runningInConsole()
                && ! $this->app->runningUnitTests()
                && extension_loaded('pcntl');
        });
    }

    /**
     * registerRouteCacheCommand
     */
    protected function registerRouteCacheCommand()
    {
        $this->app->singleton(RouteCacheCommand::class, function ($app) {
            return new RouteCacheCommand($app['files']);
        });
    }

    /**
     * registerRouteListCommand
     */
    protected function registerRouteListCommand()
    {
        $this->app->singleton(RouteListCommand::class, function ($app) {
            return new RouteListCommand($app['router']);
        });
    }

    /**
     * registerServeCommand
     */
    protected function registerServeCommand()
    {
        $this->app->singleton(ServeCommand::class, function () {
            return new ServeCommand;
        });
    }

    /**
     * registerClearCompiledCommand
     */
    protected function registerClearCompiledCommand()
    {
        $this->app->singleton(ClearCompiledCommand::class, function () {
            return new ClearCompiledCommand;
        });
    }

    /**
     * registerProjectSetCommand
     */
    protected function registerProjectSetCommand()
    {
        $this->app->singleton(ProjectSetCommand::class, function () {
            return new ProjectSetCommand;
        });
    }
}
