<?php namespace October\Rain\Foundation\Providers;

use Illuminate\Log\LogServiceProvider as LogServiceProviderBase;

class LogServiceProvider extends LogServiceProviderBase
{
    /**
     * register the service provider.
     */
    public function register()
    {
        parent::register();

        /*
         * After registration
         */
        $this->app->booting(function () {
            $this->configureDefaultLogger();
        });
    }

    /**
     * configureDefaultLogger channel for the application
     * when no configuration is supplied.
     */
    protected function configureDefaultLogger()
    {
        $config = $this->app->make('config');

        if ($config->get('logging.default', null) !== null) {
            return;
        }

        /*
         * Set default values as single log file
         */
        $config->set('logging.default', 'single');

        $config->set('logging.channels.single', [
            'driver' => 'single',
            'path' => storage_path('logs/system.log'),
            'level' => 'debug',
        ]);
    }
}
