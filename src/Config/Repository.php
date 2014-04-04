<?php namespace October\Rain\Config;

use Illuminate\Config\Repository as IlluminateRepository;
use Illuminate\Config\LoaderInterface;
use Illuminate\Config\FileLoader;
use Illuminate\Filesystem\Filesystem;

/**
 * October config repository class.
 *
 * @package config
 * @author Alexey Bobkov, Samuel Georges
 */
class Repository extends IlluminateRepository
{
    protected $appRepository;

    /**
     * Create a new translator instance.
     * @param  \Illuminate\Config\LoaderInterface  $loader
     * @param  string  $environment
     * @return void
     */
    public function __construct(LoaderInterface $loader, $environment)
    {
        parent::__construct($loader, $environment);

        $appLoader = new FileLoader(new Filesystem, app_path().'/config');
        $this->appRepository = new IlluminateRepository($appLoader, $environment);
    }

    /**
     * Add a new namespace to the loader.
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        parent::addNamespace($namespace, $hint);

        $namespaceAppPath = app_path().'/config/'.str_replace('.', '/', $namespace);
        $this->appRepository->addNamespace($namespace, $namespaceAppPath);
    }

    /**
     * Register a package for cascading configuration.
     *
     * @param  string  $package
     * @param  string  $hint
     * @param  string  $namespace
     * @return void
     */
    public function package($package, $hint, $namespace = null)
    {
        parent::package($package, $hint, $namespace);

        $namespaceAppPath = app_path().'/config/'.str_replace('.', '/', $namespace);
        $this->appRepository->package($package, $namespaceAppPath, $namespace);
    }

    /**
     * Get the config for the given key.
     * @param  string  $key
     * @param  mixed   $default
     * @return string
     */
    public function get($key, $default = null)
    {
        $value = parent::get($key, $default);
        if (strpos($key, '::') === false)
            return $value;

        $appValue = $this->appRepository->get($key, $default);

        return $appValue == $default ? $value : $appValue;
    }

}