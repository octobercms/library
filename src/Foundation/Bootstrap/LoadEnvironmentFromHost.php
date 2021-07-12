<?php namespace October\Rain\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use Exception;

/**
 * LoadEnvironmentFromHost will set the APP_ENV based on a hostname found in the
 * configuration file called environment.php. This was used in an earlier version
 * where file-based configuration was the primary method. This approach is mostly
 * incompatible with Laravel's convention so it will be sunsetted moving forward.
 *
 * @deprecated use true environment vars
 */
class LoadEnvironmentFromHost
{
    /**
     * bootstrap the given application
     */
    public function bootstrap(Application $app)
    {
        if ($config = $this->getEnvironmentConfiguration()) {
            $hostname = $_SERVER['HTTP_HOST'] ?? null;

            if ($hostname && isset($config['hosts'][$hostname])) {
                putenv("APP_ENV={$config['hosts'][$hostname]}");
            }
        }
    }

    /**
     * getEnvironmentConfiguration loads the file-based environment configuration
     */
    protected function getEnvironmentConfiguration(): ?array
    {
        $configPath = base_path().'/config/environment.php';

        if (!file_exists($configPath)) {
            return null;
        }

        try {
            $config = require $configPath;
        }
        catch (Exception $ex) {
            $config = [];
        }

        return (array) $config;
    }
}
