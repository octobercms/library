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
     * This entire method should be removed when we find out what is causing
     * the issue in Laravel 5.0.22. We can at least revert the breaking logic
     * for now. See comment here:
     * - https://github.com/laravel/framework/commit/6a9aa29278e13274549d8205f2b21a2d2cb70e98
     */
    public function __construct(\Illuminate\Contracts\Foundation\Application $app, \Illuminate\Contracts\Events\Dispatcher $events)
    {
        $this->app = $app;
        $this->events = $events;

        //
        // This causes the kerenel to boot twice...
        //
        // $this->app->booted(function()
        // {
        $this->defineConsoleSchedule();
        // });
    }

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