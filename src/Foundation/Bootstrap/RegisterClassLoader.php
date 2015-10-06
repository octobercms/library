<?php namespace October\Rain\Foundation\Bootstrap;

use October\Rain\Support\ClassLoader;
use Illuminate\Contracts\Foundation\Application;

class RegisterClassLoader
{

    /**
     * Register The October Auto Loader
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        ClassLoader::register();
        ClassLoader::addDirectories([
            $app->basePath().'/modules',
            $app->basePath().'/plugins'
        ]);
    }

}