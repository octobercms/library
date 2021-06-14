<?php namespace October\Rain\Database;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseServiceProvider as DatabaseServiceProviderBase;
use Illuminate\Database\DatabaseManager;

/**
 * DatabaseServiceProvider
 */
class DatabaseServiceProvider extends DatabaseServiceProviderBase
{
    /**
     * boot the application events
     */
    public function boot()
    {
        Model::setConnectionResolver($this->app['db']);

        Model::setEventDispatcher($this->app['events']);

        $this->swapSchemaBuilderBlueprint();
    }

    /**
     * register the service provider
     */
    public function register()
    {
        Model::clearBootedModels();

        Model::clearExtendedClasses();

        Model::flushEventListeners();

        $this->registerEloquentFactory();

        $this->registerQueueableEntityResolver();

        // The connection factory is used to create the actual connection instances on
        // the database. We will inject the factory into the manager so that it may
        // make the connections while they are actually needed and not of before.
        $this->app->singleton('db.factory', function ($app) {
            return new ConnectionFactory($app);
        });

        // The database manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.
        $this->app->singleton('db', function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });

        $this->app->bind('db.connection', function ($app) {
            return $app['db']->connection();
        });

        $this->app->singleton('db.dongle', function ($app) {
            return new Dongle($this->getDefaultDatabaseDriver(), $app['db']);
        });
    }

    /**
     * getDefaultDatabaseDriver returns the default database driver, not just the connection name
     */
    protected function getDefaultDatabaseDriver(): string
    {
        $defaultConnection = $this->app['db']->getDefaultConnection();

        return $this->app['config']['database.connections.' . $defaultConnection . '.driver'];
    }

    /**
     * swapSchemaBuilderBlueprint adds a touch of Rain to the Schema Blueprints
     */
    protected function swapSchemaBuilderBlueprint()
    {
        $this->app['events']->listen('db.schema.getBuilder', function ($builder) {
            $builder->blueprintResolver(function ($table, $callback) {
                return new Blueprint($table, $callback);
            });
        });
    }
}
