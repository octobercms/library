<?php namespace October\Rain\Foundation\Bootstrap;

use October\Rain\Config\Repository;
use October\Rain\Config\FileLoader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;

class LoadConfiguration
{

    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $fileLoader = new FileLoader(new Filesystem, base_path().'/config');
        $app->instance('config', $config = new Repository($fileLoader, $app['env']));

        date_default_timezone_set($config['app.timezone']);
        mb_internal_encoding('UTF-8');

        // Fix for XDebug aborting threads > 100 nested
        ini_set('xdebug.max_nesting_level', 300);
    }

}