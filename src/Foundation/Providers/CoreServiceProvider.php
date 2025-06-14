<?php

namespace October\Rain\Foundation\Providers;

use October\Rain\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

/**
 * CoreServiceProvider contains providers for running October Rain
 *
 * @package october\foundation
 * @author Alexey Bobkov, Samuel Georges
 */
class CoreServiceProvider extends ServiceProvider implements DeferrableProvider
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
        return [
            'core.composer',
        ];
    }
}
