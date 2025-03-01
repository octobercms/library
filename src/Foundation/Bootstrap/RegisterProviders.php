<?php

namespace October\Rain\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use October\Rain\Support\ServiceProvider;
use Illuminate\Foundation\Bootstrap\RegisterProviders as RegisterProvidersBase;

class RegisterProviders extends RegisterProvidersBase
{
    /**
     * Carbon copy of parent except defaultProviders
     */
    protected function mergeAdditionalProviders(Application $app)
    {
        if (static::$bootstrapProviderPath &&
            file_exists(static::$bootstrapProviderPath)) {
            $packageProviders = require static::$bootstrapProviderPath;

            foreach ($packageProviders as $index => $provider) {
                if (! class_exists($provider)) {
                    unset($packageProviders[$index]);
                }
            }
        }

        $app->make('config')->set(
            'app.providers',
            array_merge(
                $app->make('config')->get('app.providers') ?? ServiceProvider::defaultProviders()->toArray(),
                static::$merge,
                array_values($packageProviders ?? []),
            ),
        );
    }
}
