<?php namespace October\Rain\Foundation\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Kernel
 */
class Kernel extends ConsoleKernel
{
    /**
     * @var array bootstrappers for the application
     */
    protected $bootstrappers = [
        \October\Rain\Foundation\Bootstrap\RegisterClassLoader::class,
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \October\Rain\Foundation\Bootstrap\LoadConfiguration::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
        \Illuminate\Foundation\Bootstrap\SetRequestForConsole::class,
        \October\Rain\Foundation\Bootstrap\RegisterOctober::class,
        \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
        \Illuminate\Foundation\Bootstrap\BootProviders::class,
    ];

    /**
     * @var array commands provided by your application
     */
    protected $commands = [];

    /**
     * schedule defines the application's command schedule
     */
    protected function schedule(Schedule $schedule)
    {
        $this->bootstrap();

        $this->app['events']->dispatch('console.schedule', [$schedule]);
    }
}
