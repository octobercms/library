<?php namespace October\Rain\Resize;

use October\Rain\Support\ServiceProvider;

/**
 * ResizeServiceProvider
 *
 * @package october\resize
 * @author Alexey Bobkov, Samuel Georges
 */
class ResizeServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('resizer', function ($app) {
            return new ResizeBuilder;
        });
    }
}
