<?php namespace October\Rain\Foundation\Bootstrap;

use October\Rain\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration as LoadConfigurationBase;
use Illuminate\Contracts\Config\Repository as RepositoryContract;
use Exception;

/**
 * LoadConfiguration bootstraps the configuration instance
 */
class LoadConfiguration extends LoadConfigurationBase
{
    /**
     * bootstrap the given application.
     */
    public function bootstrap(Application $app)
    {
        $items = [];

        // First we will see if we have a cache configuration file. If we do, we'll load
        // the configuration items from that file so that it is very quick. Otherwise
        // we will need to spin through every configuration file and load them all.
        if (file_exists($cached = $app->getCachedConfigPath())) {
            $items = require $cached;

            $loadedFromCache = true;
        }

        // Next we will spin through all of the configuration files in the configuration
        // directory and load each one into the repository. This will make all of the
        // options available to the developer for use in various parts of this app.
        $app->instance('config', $config = new Repository($items));

        if (!isset($loadedFromCache)) {
            $this->loadConfigurationFiles($app, $config);
        }

        // Finally, we will set the application's environment based on the configuration
        // values that were loaded. We will pass a callback which will be used to get
        // the environment in a web context where an "--env" switch is not present.
        $app->detectEnvironment(function () use ($config) {
            return $config->get('app.env', 'production');
        });

        date_default_timezone_set($config->get('app.timezone', 'UTC'));

        mb_internal_encoding('UTF-8');

        // Fix for XDebug aborting threads > 100 nested
        ini_set('xdebug.max_nesting_level', 1000);
    }

    /**
     * loadConfigurationFiles from all of the files.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Config\Repository  $repository
     * @return void
     *
     * @throws \Exception
     */
    protected function loadConfigurationFiles(Application $app, RepositoryContract $repository)
    {
        $files = $this->getConfigurationFiles($app);

        if (!isset($files['app'])) {
            throw new Exception('Unable to load the "app" configuration file.');
        }

        foreach ($files as $key => $path) {
            // Filenames with config.php are treated as root nodes
            if (basename($path) === 'config.php') {
                $key = substr($key, 0, -7);
            }

            $repository->set($key, require $path);
        }
    }
}
