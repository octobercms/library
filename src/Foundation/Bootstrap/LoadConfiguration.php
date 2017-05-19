<?php namespace October\Rain\Foundation\Bootstrap;

use October\Rain\Config\Repository;
use October\Rain\Config\FileLoader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Exception;

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
        $fileLoader = new FileLoader($files = new Filesystem, base_path().'/config');

        $this->loadEnvironmentConfiguration($app, $files);

        $app->instance('config', $config = new Repository($fileLoader, $app['env']));

        date_default_timezone_set($config['app.timezone']);

        mb_internal_encoding('UTF-8');

        // Fix for XDebug aborting threads > 100 nested
        ini_set('xdebug.max_nesting_level', 1000);
    }

    /**
     * Load the environment configuration.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    protected function loadEnvironmentConfiguration(Application $app, Filesystem $files)
    {
        $config = [];

        if (file_exists($configPath = base_path().'/config/environment.php')) {
            try {
                $config = $files->getRequire($configPath);
            }
            catch (Exception $ex) {
                //
            }
        }

        $app->detectEnvironment(function () use ($config) {
            return $this->detectEnvironmentFromHost($config);
        });
    }

    /**
     * Returns the environment based on hostname.
     *
     * @param  array  $config
     * @return void
     */
    protected function detectEnvironmentFromHost(array $config)
    {
        $hostname = $_SERVER['HTTP_HOST'] ?? null;

        $default = env('APP_ENV', array_get($config, 'default', 'production'));

        return $hostname !== null
            ? array_get($config, 'hosts.' . $hostname, $default)
            : $default;
    }
}
