<?php namespace October\Rain\Foundation\Bootstrap;

use Illuminate\Log\Writer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\ConfigureLogging as ConfigureLoggingBase;

class ConfigureLogging extends ConfigureLoggingBase
{
    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureSingleHandler(Application $app, Writer $log)
    {
        $log->useFiles($app->storagePath().'/logs/system.log');
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureDailyHandler(Application $app, Writer $log)
    {
        $log->useDailyFiles($app->storagePath().'/logs/system.log', 5);
    }
}