<?php namespace October\Rain\Support;

use October\Contracts\Support\OctoberPackage;
use October\Rain\Support\ServiceProvider as ServiceProviderBase;

/**
 * ModuleServiceProvider
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
abstract class ModuleServiceProvider extends ServiceProviderBase implements OctoberPackage
{
    /**
     * register the service provider
     */
    public function register()
    {
        $module = $this->getModule(func_get_args());
        if (!$module) {
            return;
        }

        $modulePath = base_path('modules/' . $module);

        // Register configuration path
        $configPath = $modulePath . '/config';
        if (!$this->app->configurationIsCached() && is_dir($configPath)) {
            $this->loadConfigFrom($configPath, $module);
        }

        // Register view path
        $this->loadViewsFrom($modulePath . '/views', $module);

        // Load translator
        $this->loadTranslationsFrom($modulePath . '/lang', $module);
        if ($this->app->runningInBackend()) {
            $this->loadJsonTranslationsFrom($modulePath . '/lang');
        }

        // Add routes, if available
        $routesFile = $modulePath . '/routes.php';
        if (!$this->app->routesAreCached() && file_exists($routesFile)) {
            $this->loadRoutesFrom($routesFile);
        }
    }

    /**
     * boot bootstraps the application events
     */
    public function boot()
    {
        $module = $this->getModule(func_get_args());
        if (!$module) {
            return;
        }

        // Reserved for boot logic
    }

    /**
     * @inheritDoc
     */
    public function registerMarkupTags()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerComponents()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerPageSnippets()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerContentFields()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerNavigation()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerPermissions()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerSettings()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerSchedule($schedule)
    {
    }

    /**
     * @inheritDoc
     */
    public function registerReportWidgets()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerFormWidgets()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerFilterWidgets()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerListColumnTypes()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerMailLayouts()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerMailTemplates()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function registerMailPartials()
    {
        return [];
    }

    /**
     * getModule gets the module name from method args
     */
    protected function getModule($args)
    {
        return isset($args[0]) && is_string($args[0]) ? $args[0] : null;
    }

    /**
     * registerConsoleCommand registers a new console (artisan) command
     */
    protected function registerConsoleCommand(string $key, string $class)
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
