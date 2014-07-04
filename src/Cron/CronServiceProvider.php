<?php namespace October\Rain\Cron;

use Illuminate\Support\ServiceProvider;

class CronServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $queue = $this->app['queue'];
        $queue->addConnector('cron', function(){
            return new CronConnector;
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConsoleCommand('queue.cron', 'October\Rain\Cron\Console\CronCommand');
    }

    /**
     * Registers a new console (artisan) command
     * @param $key The command name
     * @param $class The command class
     * @return void
     */
    public function registerConsoleCommand($key, $class)
    {
        $key = 'command.'.$key;
        $this->app[$key] = $this->app->share(function($app) use ($class) {
            return new $class;
        });

        $this->commands($key);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('command.queue.cron');
    }

}
