<?php namespace October\Rain\Foundation\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The bootstrap classes for the application.
     *
     * @var array
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
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $this->bootstrap();
        $this->app['events']->fire('console.schedule', [$schedule]);
    }
}
