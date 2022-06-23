<?php namespace October\Rain\Combine;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

/**
 * CombinerServiceProvider
 *
 * @package october/combine
 * @author Alexey Bobkov, Samuel Georges
 */
class CombinerServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * register the service provider.
     */
    public function register()
    {
        $this->app->singleton('combiner', function ($app) {
            return new Combiner;
        });
    }

    /**
     * provides the returned services.
     * @return array
     */
    public function provides()
    {
        return [
            'combiner',
        ];
    }
}
