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
        'October\Rain\Foundation\Bootstrap\RegisterClassLoader',
        'Illuminate\Foundation\Bootstrap\DetectEnvironment',
        'October\Rain\Foundation\Bootstrap\LoadConfiguration',
        'October\Rain\Foundation\Bootstrap\LoadTranslation',
        'October\Rain\Foundation\Bootstrap\ConfigureLogging',
        'Illuminate\Foundation\Bootstrap\HandleExceptions',
        'Illuminate\Foundation\Bootstrap\RegisterFacades',
        'Illuminate\Foundation\Bootstrap\SetRequestForConsole',
        'October\Rain\Foundation\Bootstrap\RegisterOctober',
        'Illuminate\Foundation\Bootstrap\RegisterProviders',
        'Illuminate\Foundation\Bootstrap\BootProviders',
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