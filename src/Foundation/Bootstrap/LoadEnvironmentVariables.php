<?php namespace October\Rain\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables as LoadEnvironmentVariablesBase;

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
        parent::bootstrap($app);

        $app->detectEnvironment(function () {
            return env('APP_ENV', 'production');
        });
    }
}
