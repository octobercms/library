<?php namespace October\Rain\Support;

use October\Rain\Support\Facades\File;
use Illuminate\Support\ServiceProvider as ServiceProviderBase;

/**
 * ModuleServiceProvider
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
abstract class ModuleServiceProvider extends ServiceProviderBase
{
    /**
     * @var bool defer indicates if loading of the provider is deferred
     */
    protected $defer = false;

    /**
     * boot bootstraps the application events
     */
    public function boot()
    {
        $module = $this->getModule(func_get_args());
        if (!$module) {
            return;
        }

        // Register paths for: config, translator, view
        $modulePath = base_path() . '/modules/' . $module;
        $this->loadViewsFrom($modulePath . '/views', $module);
        $this->loadTranslationsFrom($modulePath . '/lang', $module);
        $this->loadConfigFrom($modulePath . '/config', $module);

        if ($this->app->runningInBackend()) {
            $this->loadJsonTranslationsFrom($modulePath . '/lang');
        }
    }

    /**
     * register the service provider
     */
    public function register()
    {
        $module = $this->getModule(func_get_args());
        if (!$module) {
            return;
        }

        // Add routes, if available
        $routesFile = base_path() . '/modules/' . $module . '/routes.php';
        if (File::isFile($routesFile)) {
            $this->loadRoutesFrom($routesFile);
        }
    }

    /**
     * provides gets the services provided by the provider
     */
    public function provides()
    {
        return [];
    }

    /**
     * getModule gets the module name from method args
     */
    public function getModule($args)
    {
        return (isset($args[0]) and is_string($args[0])) ? $args[0] : null;
    }

    /**
     * registerConsoleCommand registers a new console (artisan) command
     */
    public function registerConsoleCommand(string $key, string $class)
    {
        $key = 'command.'.$key;

        $this->app->singleton($key, function ($app) use ($class) {
            return $this->app->make($class);
        });

        $this->commands($key);
    }

    /**
     * loadConfigFrom registers a config file namespace
     * @param  string  $path
     * @param  string  $namespace
     */
    protected function loadConfigFrom($path, $namespace)
    {
        $this->app['config']->package($namespace, $path);
    }
}
