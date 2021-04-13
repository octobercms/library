<?php namespace October\Rain\Foundation\Bootstrap;

use October\Rain\Config\Repository;
use October\Rain\Config\FileLoader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;

/**
 * LoadConfiguration bootstraps the configuration instance
 */
class LoadConfiguration
{
    /**
     * bootstrap the given application.
     */
    public function bootstrap(Application $app)
    {
        $fileLoader = new FileLoader(new Filesystem, base_path().'/config');

        $app->instance('config', $config = new Repository($fileLoader, $app['env']));

        $app->detectEnvironment(function () use ($config) {
            return $config->get('app.env', 'production');
        });

        date_default_timezone_set($config['app.timezone']);

        mb_internal_encoding('UTF-8');

        // Fix for XDebug aborting threads > 100 nested
        ini_set('xdebug.max_nesting_level', 1000);
    }
}
