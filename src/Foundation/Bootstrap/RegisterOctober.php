<?php namespace October\Rain\Foundation\Bootstrap;

use October\Rain\Support\Str;
use October\Rain\Support\ClassLoader;
use Illuminate\Contracts\Foundation\Application;

/**
 * RegisterOctober specific features
 */
class RegisterOctober
{
    /**
     * bootstrap
     */
    public function bootstrap(Application $app)
    {
        /*
         * Workaround for CLI and URL based in subdirectory
         */
        if ($app->runningInConsole()) {
            $app['url']->forceRootUrl($app['config']->get('app.url'));
        }

        /*
         * Register singletons
         */
        $app->singleton('string', function () {
            return new \October\Rain\Support\Str;
        });

        /*
         * Change paths based on config
         */
        if ($storagePath = $app['config']->get('system.storage_path')) {
            $app->setStoragePath($this->parseConfiguredPath($app, $storagePath));
        }

        if ($cachePath = $app['config']->get('system.cache_path')) {
            $app->setCachePath($this->parseConfiguredPath($app, $cachePath));
        }

        if ($pluginsPath = $app['config']->get('system.plugins_path')) {
            $app->setPluginsPath($this->parseConfiguredPath($app, $pluginsPath));
        }

        if ($themesPath = $app['config']->get('system.themes_path')) {
            $app->setThemesPath($this->parseConfiguredPath($app, $themesPath));
        }

        $this->makeSystemPaths($app->cachePath(), [
            'cms',
            'cms/cache',
            'cms/combiner',
            'cms/twig',
            'framework',
            'framework/cache',
            'framework/views',
            'temp',
            'temp/public',
        ]);

        $this->makeSystemPaths($app->storagePath(), [
            'app',
            'app/media',
            'app/uploads',
            'framework',
            'framework/sessions',
            'logs',
        ]);

        /*
         * Initialize class loader cache
         */
        $loader = $app->make(ClassLoader::class);
        $loader->initManifest($app->getCachedClassesPath());
    }

    /**
     * parseConfiguredPath will include the base path if necessary
     */
    protected function parseConfiguredPath(Application $app, string $path): string
    {
        return Str::startsWith($path, '/')
            ? $path
            : $app->basePath($path);
    }

    /**
     * makeSystemPaths will attempt to ensure the required system paths exist
     */
    protected function makeSystemPaths(string $rootPath, array $subPaths): void
    {
        if (file_exists($rootPath)) {
            return;
        }

        @mkdir($rootPath);

        foreach ($subPaths as $path) {
            $subPath = $rootPath.DIRECTORY_SEPARATOR.$path;
            if (file_exists($subPath)) {
                continue;
            }

            @mkdir($subPath);
        }
    }
}
