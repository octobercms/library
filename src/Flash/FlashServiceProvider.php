<?php namespace October\Rain\Flash;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;

class FlashServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * The container singletons that should be registered.
     *
     * @var array
     */
    public $singletons = [
        'flash' => FlashBag::class,
    ];

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['flash'];
    }
}
