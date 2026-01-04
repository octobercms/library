<?php namespace October\Rain\Database;

use October\Rain\Database\Updater;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseServiceProvider as DatabaseServiceProviderBase;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\DatabaseTransactionsManager;

/**
 * DatabaseServiceProvider
 */
class DatabaseServiceProvider extends DatabaseServiceProviderBase
{
    /**
     * register the service provider
     */
    public function register()
    {
        Model::clearBootedModels();
        Model::flushEventListeners();

        $this->registerConnectionServices();
        $this->registerFakerGenerator();
        $this->registerQueueableEntityResolver();
    }

    /**
     * boot the application events
     */
    public function boot()
    {
        Model::setConnectionResolver($this->app['db']);
        Model::setEventDispatcher($this->app['events']);

        $this->configureStrictLoading();
    }

    /**
     * configureStrictLoading sets up lazy loading prevention based on environment.
     * @see \Illuminate\Database\Eloquent\Model::preventLazyLoading()
     */
    protected function configureStrictLoading(): void
    {
        $strictMode = $this->app['config']->get('database.strict_loading');

        if ($strictMode === null) {
            $strictMode = !$this->app->isProduction();
        }

        if ($strictMode && method_exists(Model::class, 'preventLazyLoading')) {
            Model::preventLazyLoading();
        }

        if (
            $this->app->isProduction() &&
            $this->app['config']->get('database.log_lazy_loading', false) &&
            method_exists(Model::class, 'handleLazyLoadingViolationUsing')
        ) {
            Model::handleLazyLoadingViolationUsing(function ($model, $relation) {
                $this->app['log']->warning("Lazy loading violation: {$relation} on " . get_class($model));
            });
        }
    }

    /**
     * registerConnectionServices for the primary database bindings.
     */
    protected function registerConnectionServices()
    {
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

        $this->app->bind('db.schema', function ($app) {
            $builder = $app['db']->connection()->getSchemaBuilder();

            // Custom blueprint resolver for schema
            $builder->blueprintResolver(function ($connection, $table, $callback) {
                return new Blueprint($connection, $table, $callback);
            });

            return $builder;
        });

        $this->app->singleton('db.transactions', function ($app) {
            return new DatabaseTransactionsManager;
        });

        $this->app->bind('db.replicator', Replicator::class);

        $this->app->singleton('db.dongle', function ($app) {
            return new Dongle($this->getDefaultDatabaseDriver(), $app['db']);
        });

        $this->app->singleton('db.updater', function ($app) {
            return new Updater;
        });
    }

    /**
     * getDefaultDatabaseDriver returns the default database driver, not just the connection name
     */
    protected function getDefaultDatabaseDriver(): string
    {
        $defaultConnection = $this->app['db']->getDefaultConnection();

        return $this->app['config']["database.connections.{$defaultConnection}.driver"];
    }
}
