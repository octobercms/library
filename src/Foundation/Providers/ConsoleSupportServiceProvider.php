<?php

namespace October\Rain\Foundation\Providers;

use Illuminate\Foundation\Providers\ConsoleSupportServiceProvider as ConsoleSupportServiceProviderBase;

class ConsoleSupportServiceProvider extends ConsoleSupportServiceProviderBase
{
    /**
     * The provider class names.
     *
     * @var string[]
     */
    protected $providers = [
        \October\Rain\Foundation\Providers\ArtisanServiceProvider::class,
        \Illuminate\Database\MigrationServiceProvider::class,
        \Illuminate\Foundation\Providers\ComposerServiceProvider::class,
    ];
}
