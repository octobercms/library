<?php namespace October\Rain\Foundation\Providers;

use Illuminate\Support\AggregateServiceProvider;
use October\Rain\Extension\Container as OctoberContainer;

/**
 * AppSupportServiceProvider supplies eager providers
 */
class AppSupportServiceProvider extends AggregateServiceProvider
{
    /**
     * provides gets the services provided by the provider
     */
    protected $providers = [
        \October\Rain\Database\DatabaseServiceProvider::class,
        \October\Rain\Halcyon\HalcyonServiceProvider::class,
        \October\Rain\Filesystem\FilesystemServiceProvider::class,
        \October\Rain\Html\UrlServiceProvider::class,
        \October\Rain\Argon\ArgonServiceProvider::class
    ];

    /**
     * register the service provider
     */
    public function register()
    {
        OctoberContainer::clearExtensions();
    }
}
