<?php namespace October\Rain\Database;

use October\Rain\Database\Updater;
use Illuminate\Database\MigrationServiceProvider as MigrationServiceProviderBase;

/**
 * MigrationServiceProvider is a proxy class, it is deferred unlike DatabaseServiceProvider
 */
class MigrationServiceProvider extends MigrationServiceProviderBase
{
    /**
     * register the service provider.
     */
    public function register()
    {
        parent::register();

        $this->registerUpdater();
    }

    /**
     * registerUpdater is like the migrator service, but it updates plugins.
     */
    protected function registerUpdater()
    {
        $this->app->singleton('db.updater', function ($app) {
            return new Updater;
        });
    }
}
