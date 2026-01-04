<?php namespace October\Rain\Assetic;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

/**
 * AsseticServiceProvider
 *
 * @package october/assetic
 * @author Alexey Bobkov, Samuel Georges
 */
class AsseticServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton('assetic', function ($app) {
            $combiner = new Combiner;
            $combiner->setStoragePath(storage_path('cms/combiner/assets'));
            $combiner->registerDefaultFilters();
            return $combiner;
        });
    }

    /**
     * Provides the returned services.
     */
    public function provides(): array
    {
        return [
            'assetic',
        ];
    }
}
