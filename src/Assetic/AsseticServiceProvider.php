<?php namespace October\Rain\Assetic;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

/**
 * AsseticServiceProvider
 */
class AsseticServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * register the service provider.
     */
    public function register()
    {
        $this->app->singleton('assetic', function ($app) {
            $combiner = new Combiner;
            $combiner->setStoragePath(storage_path('cms/combiner/assets'));
            $combiner->registerDefaultFilters();
            return $combiner;
        });
    }

    /**
     * provides the returned services.
     * @return array
     */
    public function provides()
    {
        return [
            'assetic',
        ];
    }
}
