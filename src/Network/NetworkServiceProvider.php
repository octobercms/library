<?php namespace October\Rain\Network;

use Illuminate\Support\ServiceProvider;

class NetworkServiceProvider extends ServiceProvider
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
        $this->app->singleton('network.http', function ($app) {
            return new Http;
        });
    }

    /**
     * provides gets the services provided by the provider
     */
    public function provides()
    {
        return ['network.http'];
    }
}
