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
        '\October\Rain\Foundation\Bootstrap\RegisterClassLoader',
        '\October\Rain\Foundation\Bootstrap\LoadEnvironmentVariables',
        '\October\Rain\Foundation\Bootstrap\LoadConfiguration',
        '\October\Rain\Foundation\Bootstrap\LoadTranslation',
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
        \Illuminate\Foundation\Bootstrap\SetRequestForConsole::class,
        '\October\Rain\Foundation\Bootstrap\RegisterOctober',
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

        $this->app['events']->fire('console.schedule', [$schedule]);
    }
}
