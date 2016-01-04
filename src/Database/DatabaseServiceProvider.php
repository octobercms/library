<?php namespace October\Rain\Database;

use October\Rain\Database\Model;
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
     * @return void
     */
    public function register()
    {
        parent::register();

        Model::clearExtendedClasses();

        $this->app->singleton('db.dongle', function($app)
        {
            return new Dongle($this->getDefaultDatabaseDriver(), $app['db']);
        });
    }

    /**
     * Returns the default database driver, not just the connection name.
     * @return string
     */
    protected function getDefaultDatabaseDriver()
    {
        $defaultConnection = $this->app['db']->getDefaultConnection();
        return $this->app['config']['database.connections.' . $defaultConnection . '.driver'];
    }

}