<?php namespace October\Rain\Foundation\Providers;

use Illuminate\Log\Writer;
use Illuminate\Log\LogServiceProvider as LogServiceProviderBase;

class LogServiceProvider extends LogServiceProviderBase
{
    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureSingleHandler(Writer $log)
    {
        $log->useFiles(
            $this->app->storagePath().'/logs/system.log',
            $this->logLevel()
        );
    }

    /**
     * Configure the Monolog handlers for the application.
     *
     * @param  \Illuminate\Log\Writer  $log
     * @return void
     */
    protected function configureDailyHandler(Writer $log)
    {
        $log->useDailyFiles(
            $this->app->storagePath().'/logs/system.log', $this->maxFiles(),
            $this->logLevel()
        );
    }
}
