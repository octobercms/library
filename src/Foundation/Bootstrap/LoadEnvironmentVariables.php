<?php namespace October\Rain\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables as LoadEnvironmentVariablesBase;
use Dotenv\Exception\InvalidFileException;

/**
 * LoadEnvironmentVariables
 */
class LoadEnvironmentVariables extends LoadEnvironmentVariablesBase
{
    /**
     * bootstrap the given application
     */
    public function bootstrap(Application $app)
    {
        $this->checkForSpecificEnvironmentFile($app);

        try {
            $this->createDotenv($app)->safeLoad();
        }
        catch (InvalidFileException $e) {
            $this->writeErrorAndDie($e);
        }
    }
}
