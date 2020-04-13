<?php namespace October\Rain\Database;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseServiceProvider as DatabaseServiceProviderBase;
use Illuminate\Database\DatabaseManager;

class DatabaseServiceProvider extends DatabaseServiceProviderBase
{

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        Model::setConnectionResolver($this->app['db']);

        Model::setEventDispatcher($this->app['events']);

        $this->swapSchemaBuilderBlueprint();
    }

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        Model::clearBootedModels();

        Model::clearExtendedClasses();

        Model::flushDuplicateCache();

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

        // Disable memory cache when running in console environment. This should
        // prevent daemon processes from handling stale data in memory, however
        // it should be kept active for the purpose of accurate unit testing.
        if ($this->app->runningInConsole() && !$this->app->runningUnitTests()) {
            MemoryCache::instance()->enabled(false);
        }
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
        $this->app['events']->listen('db.schema.getBuilder', function (\Illuminate\Database\Schema\Builder $builder) {
            $builder->blueprintResolver(function ($table, $callback) {
                return new Blueprint($table, $callback);
            });
        });
    }
}
