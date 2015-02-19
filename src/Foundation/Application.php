<?php namespace October\Rain\Foundation;

use Illuminate\Foundation\Application as ApplicationBase;

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
     * @param  string  $basePath
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
     * @param  string  $basePath
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


}