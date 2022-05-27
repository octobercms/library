<?php namespace October\Rain\Resize;

use October\Rain\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

/**
 * ResizeServiceProvider
 *
 * @package october\resize
 * @author Alexey Bobkov, Samuel Georges
 */
class ResizeServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * register the service provider.
     */
    public function register()
    {
        $this->app->singleton('resizer', function ($app) {
            return new ResizeBuilder;
        });
    }

    /**
     * provides the returned services.
     * @return array
     */
    public function provides()
    {
        return ['resizer'];
    }
}
