<?php namespace October\Rain\Foundation\Providers;

use October\Rain\Foundation\Maker;
use Illuminate\Support\ServiceProvider;

class MakerServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Maker::class);
    }
}
