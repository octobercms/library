<?php namespace October\Rain\Foundation;

use Closure;
use Illuminate\Foundation\Application as ApplicationBase;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Exception;

class Application extends ApplicationBase
{
    /**
     * The base path for plugins.
     *
     * @var string
     */
    protected $pluginsPath;

    /**
     * The base path for themes.
     *
     * @var string
     */
    protected $themesPath;

    /**
     * The request execution context (front-end, back-end)
     *
     * @var string
     */
    protected $executionContext;

    /**
     * Get the path to the public / web directory.
     *
     * @return string
     */
    public function publicPath()
    {
        return $this->basePath;
    }

    /**
     * Get the path to the language files.
     *
     * @return string
     */
    public function langPath()
    {
        return $this->basePath.'/lang';
    }

    /**
     * Bind all of the application paths in the container.
     *
     * @return void
     */
    protected function bindPathsInContainer()
    {
        parent::bindPathsInContainer();

        foreach (['plugins', 'themes', 'temp'] as $path) {
            $this->instance('path.'.$path, $this->{$path.'Path'}());
        }
    }

    /**
     * Get the path to the public / web directory.
     *
     * @return string
     */
    public function pluginsPath()
    {
        return $this->pluginsPath ?: $this->basePath.'/plugins';
    }

    /**
     * Set the plugins path for the application.
     *
     * @param  string $path
     * @return $this
     */
    public function setPluginsPath($path)
    {
        $this->pluginsPath = $path;
        $this->instance('path.plugins', $path);
        return $this;
    }

    /**
     * Get the path to the public / web directory.
     *
     * @return string
     */
    public function themesPath()
    {
        return $this->themesPath ?: $this->basePath.'/themes';
    }

    /**
     * Set the themes path for the application.
     *
     * @param  string $path
     * @return $this
     */
    public function setThemesPath($path)
    {
        $this->themesPath = $path;
        $this->instance('path.themes', $path);
        return $this;
    }

    /**
     * Get the path to the public / web directory.
     *
     * @return string
     */
    public function tempPath()
    {
        return $this->basePath.'/storage/temp';
    }

    /**
     * Register a "before" application filter.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public function before($callback)
    {
        return $this['router']->before($callback);
    }

    /**
     * Register an "after" application filter.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public function after($callback)
    {
        return $this['router']->after($callback);
    }

    /**
     * Register an application error handler.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function error(Closure $callback)
    {
        $this->make('Illuminate\Contracts\Debug\ExceptionHandler')->error($callback);
    }

    /**
     * Register an error handler for fatal errors.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function fatal(Closure $callback)
    {
        $this->error(function(FatalErrorException $e) use ($callback) {
            return call_user_func($callback, $e);
        });
    }

    /**
     * Determine if we are running in the back-end area.
     *
     * @return bool
     */
    public function runningInBackend()
    {
        return $this->executionContext == 'back-end';
    }

    /**
     * Sets the execution context
     *
     * @param  string  $context
     * @return void
     */
    public function setExecutionContext($context)
    {
        $this->executionContext = $context;
    }

    /**
     * Returns true if a database connection is present.
     * @return boolean
     */
    public function hasDatabase()
    {
        try {
            $this['db.connection']->getDatabaseName();
        }
        catch (Exception $ex) {
            return false;
        }

        return true;
    }

    //
    // Caching
    //

    /**
     * Get the path to the configuration cache file.
     *
     * @return string
     */
    public function getCachedConfigPath()
    {
        return $this['path.storage'].'/framework/config.php';
    }

    /**
     * Get the path to the routes cache file.
     *
     * @return string
     */
    public function getCachedRoutesPath()
    {
        return $this['path.storage'].'/framework/routes.php';
    }

    /**
     * Get the path to the cached "compiled.php" file.
     *
     * @return string
     */
    public function getCachedCompilePath()
    {
        return $this->storagePath().'/framework/compiled.php';
    }

    /**
     * Get the path to the cached services.json file.
     *
     * @return string
     */
    public function getCachedServicesPath()
    {
        return $this->storagePath().'/framework/services.json';
    }

}