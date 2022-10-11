<?php namespace October\Rain\Flash;

use Illuminate\Support\ServiceProvider;

class FlashServiceProvider extends ServiceProvider
{
    /**
     * @var bool defer indicates if loading of the provider is deferred
     */
    protected $defer = false;

    /**
     * register the service provider.
     */
    public function register()
    {
        $this->app->singleton('flash', function () {
            return new FlashBag();
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
