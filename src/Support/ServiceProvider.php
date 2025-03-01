<?php namespace October\Rain\Support;

use Illuminate\Support\ServiceProvider as ServiceProviderBase;

/**
 * ServiceProvider is an empty umbrella class
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
abstract class ServiceProvider extends ServiceProviderBase
{
    /**
     * @var \October\Rain\Foundation\Application app instance
     */
    protected $app;

    /**
     * callBeforeResolving sets up a before resolving listener, or fire immediately
     * if already resolved.
     *
     * @param  string  $name
     * @param  callable  $callback
     * @return void
     */
    protected function callBeforeResolving($name, $callback)
    {
        $this->app->beforeResolving($name, $callback);

        if ($this->app->resolved($name)) {
            $callback($this->app->make($name), $this->app);
        }
    }

    /**
     * Get the default providers for a Laravel application.
     *
     * @return \October\Rain\Support\DefaultProviders
     */
    public static function defaultProviders()
    {
        return new DefaultProviders;
    }
}
