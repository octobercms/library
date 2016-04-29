<?php namespace October\Rain\Database;

use October\Rain\Database\Model;
use October\Rain\Database\Schema\Blueprint;
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

        $this->swapSchemaBuilderBlueprint();
    }

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        parent::register();

        Model::clearExtendedClasses();

        $this->app->singleton('db.dongle', function($app) {
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

    /**
     * Adds a touch of Rain to the Schema Blueprints.
     * @return void
     */
    protected function swapSchemaBuilderBlueprint()
    {
        $this->app['events']->listen('db.schema.getBuilder', function($builder) {
            $builder->blueprintResolver(function($table, $callback) {
                return new Blueprint($table, $callback);
            });
        });
    }

}
