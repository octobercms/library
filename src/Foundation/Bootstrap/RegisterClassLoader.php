<?php namespace October\Rain\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use October\Rain\Filesystem\Filesystem;
use October\Rain\Support\ClassLoader;

/**
 * RegisterClassLoader registers the custom autoloader for October CMS
 */
class RegisterClassLoader
{
    /**
     * bootstrap
     */
    public function bootstrap(Application $app)
    {
        $loader = new ClassLoader(
            new Filesystem,
            $app->basePath()
        );

        $app->instance(ClassLoader::class, $loader);

        $loader->register();

        $loader->addNamespace('App\\', '');

        $loader->addDirectories([
            'modules',
            'plugins'
        ]);

        $app->after(function () use ($loader) {
            $loader->build();
        });
    }
}
