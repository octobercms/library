<?php namespace October\Rain\Composer;

use October\Rain\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

/**
 * ComposerServiceProvider
 *
 * @package october\composer
 * @author Alexey Bobkov, Samuel Georges
 */
class ComposerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * register the service provider.
     */
    public function register()
    {
        $this->app->singleton('core.composer', \October\Rain\Composer\ComposerManager::class);
    }

    /**
     * provides the returned services.
     * @return array
     */
    public function provides()
    {
        return ['core.composer'];
    }
}
