<?php namespace October\Rain\Flash;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class FlashServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * register the service provider.
     */
    public function register()
    {
        $this->app->singleton('flash', function () {
            return new FlashBag;
        });

        $this->app->alias('flash', FlashBag::class);
    }

    /**
     * provides gets the services provided by the provider
     */
    public function provides()
    {
        return ['flash'];
    }
}
