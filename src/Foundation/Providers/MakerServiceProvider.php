<?php namespace October\Rain\Foundation\Providers;

use October\Rain\Foundation\Maker;
use Illuminate\Support\ServiceProvider;

class MakerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Maker::class);
    }
}
