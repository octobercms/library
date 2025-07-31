<?php

namespace October\Rain\Foundation\Providers;

use Illuminate\Database\MigrationServiceProvider;
use October\Rain\Foundation\Providers\ArtisanServiceProvider;
use Illuminate\Foundation\Providers\ComposerServiceProvider;
use Illuminate\Foundation\Providers\ConsoleSupportServiceProvider as ConsoleSupportServiceProviderBase;

class ConsoleSupportServiceProvider extends ConsoleSupportServiceProviderBase
{
    /**
     * The provider class names.
     *
     * @var string[]
     */
    protected $providers = [
        ArtisanServiceProvider::class,
        MigrationServiceProvider::class,
        ComposerServiceProvider::class,
    ];
}
