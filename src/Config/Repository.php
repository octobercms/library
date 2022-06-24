<?php namespace October\Rain\Config;

use Illuminate\Config\Repository as RepositoryBase;
use Arr;

/**
 * Repository for configuration in October CMS
 *
 * @package october/config
 * @author Alexey Bobkov, Samuel Georges
 */
class Repository extends RepositoryBase
{
    /**
     * package registers a package configuration
     * @param  string  $namespace
     * @param  string  $path
     * @return void
     */
    public function package($namespace, $path)
    {
        // Locate config files found in the package
        foreach (FileLoader::fromPath($path) as $key => $filePath) {

            // Filenames with config.php are treated as root nodes
            $configKey = $key === 'config' ? $namespace : $namespace . '.' . $key;

            // Core config overrides package config
            $coreConfig = $this->get($configKey, []);
            $baseConfig = require $filePath;
            $this->set($configKey, $coreConfig + $baseConfig);
        }
    }

    /**
     * has determines if the given configuration value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        return Arr::has($this->items, $this->toNsKey($key));
    }

    /**
     * get the specified configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }

        return Arr::get($this->items, $this->toNsKey($key), $default);
    }

    /**
     * getMany configuration values.
     *
     * @param  array  $keys
     * @return array
     */
    public function getMany($keys)
    {
        $config = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                [$key, $default] = [$default, null];
            }

            $newKey = $this->toNsKey($key);
            $config[$newKey] = Arr::get($this->items, $newKey, $default);
        }

        return $config;
    }

    /**
     * set a given configuration value.
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return void
     */
    public function set($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $key => $value) {
            Arr::set($this->items, $this->toNsKey($key), $value);
        }
    }

    /**
     * toNsKey converts a namespaced key to an array key
     */
    protected function toNsKey($key)
    {
        if (strpos($key, '::') !== false) {
            return str_replace(['::config', '::'], ['', '.'], $key);
        }

        return $key;
    }
}
