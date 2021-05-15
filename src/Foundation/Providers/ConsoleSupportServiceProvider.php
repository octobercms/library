<?php namespace October\Rain\Foundation\Providers;

use October\Rain\Database\MigrationServiceProvider;
use Illuminate\Support\AggregateServiceProvider;
use Illuminate\Foundation\Providers\ComposerServiceProvider;

class ConsoleSupportServiceProvider extends AggregateServiceProvider
{
    /**
     * @var bool defer indicates if loading of the provider is deferred
     */
    protected $defer = true;

    /**
     * provides gets the services provided by the provider
     */
    protected $providers = [
        ArtisanServiceProvider::class,
        MigrationServiceProvider::class,
        ComposerServiceProvider::class,
    ];
}
