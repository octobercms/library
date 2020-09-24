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
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $fileLoader = new FileLoader(new Filesystem, $app['path.config']);

        $app->detectEnvironment(function () use ($app) {
            return $this->getEnvironmentFromHost($app);
        });

        $app->instance('config', $config = new Repository($fileLoader, $app['env']));

        date_default_timezone_set($config['app.timezone']);

        mb_internal_encoding('UTF-8');

        // Fix for XDebug aborting threads > 100 nested
        ini_set('xdebug.max_nesting_level', 1000);
    }

    /**
     * Returns the environment based on hostname.
     * @param  array  $config
     * @return void
     */
    protected function getEnvironmentFromHost(Application $app)
    {
        $config = $this->getEnvironmentConfiguration();

        $hostname = $_SERVER['HTTP_HOST'] ?? null;

        if ($hostname && isset($config['hosts'][$hostname])) {
            return $config['hosts'][$hostname];
        }

        return env('APP_ENV', array_get($config, 'default', 'production'));
    }

    /**
     * Load the environment configuration.
     * @return array
     */
    protected function getEnvironmentConfiguration()
    {
        $config = [];

        $environment = env('APP_ENV');

        if ($environment && file_exists($configPath = base_path().'/config/'.$environment.'/environment.php')) {
            try {
                $config = require $configPath;
            }
            catch (Exception $ex) {
                //
            }
        }
        elseif (file_exists($configPath = base_path().'/config/environment.php')) {
            try {
                $config = require $configPath;
            }
            catch (Exception $ex) {
                //
            }
        }

        return $config;
    }
}
