<?php namespace October\Rain\Database;

use Illuminate\Database\DatabaseServiceProvider as DatabaseServiceProviderBase;

class DatabaseServiceProvider extends DatabaseServiceProviderBase
{

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->app->bindShared('db.dongle', function($app)
        {
            return new Dongle($app['db']->getDefaultConnection(), $app['db']);
        });
    }

}
