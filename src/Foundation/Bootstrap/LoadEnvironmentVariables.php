<?php namespace October\Rain\Foundation\Bootstrap;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Symfony\Component\Console\Input\ArgvInput;
use Illuminate\Contracts\Foundation\Application;

class LoadEnvironmentVariables
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $this->checkForSpecificEnvironmentFile($app);

        try {
            DotEnv::create($app->environmentPath(), $app->environmentFile())->load();
        }
        catch (InvalidPathException $e) {
            //
        }

        $app->detectEnvironment(function () {
            return env('APP_ENV', 'production');
        });
    }

    /**
     * Detect if a custom environment file matching the APP_ENV exists.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    protected function checkForSpecificEnvironmentFile($app)
    {
        if ($app->runningInConsole() && ($input = new ArgvInput)->hasParameterOption('--env')) {
            $this->setEnvironmentFilePath(
                $app,
                $app->environmentFile().'.'.$input->getParameterOption('--env')
            );
        }

        if (!env('APP_ENV')) {
            return;
        }

        $this->setEnvironmentFilePath(
            $app,
            $app->environmentFile().'.'.env('APP_ENV')
        );
    }

    /**
     * Load a custom environment file.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  string  $file
     * @return void
     */
    protected function setEnvironmentFilePath($app, $file)
    {
        if (file_exists($app->environmentPath().'/'.$file)) {
            $app->loadEnvironmentFrom($file);
        }
    }
}
