<?php namespace October\Rain\Config;

use Closure;
use ArrayAccess;
use Illuminate\Contracts\Config\Repository as RepositoryContract;

/**
 * October config repository class.
 *
 * @package config
 * @author Alexey Bobkov, Samuel Georges
 */
class Repository implements ArrayAccess, RepositoryContract
{
    use \October\Rain\Support\Traits\KeyParser;

    /**
     * The loader implementation.
     *
     * @var \October\Rain\Config\LoaderInterface
     */
    protected $loader;

    /**
     * The current environment.
     *
     * @var string
     */
    protected $environment;

    /**
     * All of the configuration items.
     *
     * @var array
     */
    protected $items = [];

    /**
     * All of the registered packages.
     *
     * @var array
     */
    protected $packages = [];

    /**
     * The after load callbacks for namespaces.
     *
     * @var array
     */
    protected $afterLoad = [];

    /**
     * Create a new configuration repository.
     *
     * @param  \October\Rain\Config\LoaderInterface  $loader
     * @param  string  $environment
     * @return void
     */
    public function __construct(LoaderInterface $loader, $environment)
    {
        $this->loader = $loader;
        $this->environment = $environment;
    }

    /**
     * Determine if the given configuration value exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key)
    {
        $default = microtime(true);

        return $this->get($key, $default) !== $default;
    }

    /**
     * Determine if a configuration group exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasGroup($key)
    {
        list($namespace, $group, $item) = $this->parseConfigKey($key);

        return $this->loader->exists($group, $namespace);
    }

    /**
     * Get the specified configuration value.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        list($namespace, $group, $item) = $this->parseConfigKey($key);

        // Configuration items are actually keyed by "collection", which is simply a
        // combination of each namespace and groups, which allows a unique way to
        // identify the arrays of configuration items for the particular files.
        $collection = $this->getCollection($group, $namespace);

        $this->load($group, $namespace, $collection);

        return array_get($this->items[$collection], $item, $default);
    }

    /**
     * Set a given configuration value.
     *
     * @param  array|string  $key
     * @param  mixed   $value
     * @return void
     */
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $innerKey => $innerValue) {
                $this->set($innerKey, $innerValue);
            }
        }
        else {
            list($namespace, $group, $item) = $this->parseConfigKey($key);

            $collection = $this->getCollection($group, $namespace);

            // We'll need to go ahead and lazy load each configuration groups even when
            // we're just setting a configuration item so that the set item does not
            // get overwritten if a different item in the group is requested later.
            $this->load($group, $namespace, $collection);

            if (is_null($item)) {
                $this->items[$collection] = $value;
            }
            else {
                array_set($this->items[$collection], $item, $value);
            }
        }
    }

    /**
     * Prepend a value onto an array configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function prepend($key, $value)
    {
        $array = $this->get($key);

        array_unshift($array, $value);

        $this->set($key, $array);
    }

    /**
     * Push a value onto an array configuration value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function push($key, $value)
    {
        $array = $this->get($key);

        $array[] = $value;

        $this->set($key, $array);
    }

    /**
     * Get all of the configuration items for the application.
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Load the configuration group for the key.
     *
     * @param  string  $group
     * @param  string  $namespace
     * @param  string  $collection
     * @return void
     */
    protected function load($group, $namespace, $collection)
    {
        $env = $this->environment;

        // If we've already loaded this collection, we will just bail out since we do
        // not want to load it again. Once items are loaded a first time they will
        // stay kept in memory within this class and not loaded from disk again.
        if (isset($this->items[$collection])) {
            return;
        }

        $items = $this->loader->load($env, $group, $namespace);

        // If we've already loaded this collection, we will just bail out since we do
        // not want to load it again. Once items are loaded a first time they will
        // stay kept in memory within this class and not loaded from disk again.
        if (isset($this->afterLoad[$namespace])) {
            $items = $this->callAfterLoad($namespace, $group, $items);
        }

        $this->items[$collection] = $items;
    }

    /**
     * Call the after load callback for a namespace.
     *
     * @param  string  $namespace
     * @param  string  $group
     * @param  array   $items
     * @return array
     */
    protected function callAfterLoad($namespace, $group, $items)
    {
        $callback = $this->afterLoad[$namespace];

        return call_user_func($callback, $this, $group, $items);
    }

    /**
     * Parse a key into namespace, group, and item.
     *
     * @param  string  $key
     * @return array
     */
    public function parseConfigKey($key)
    {
        if (strpos($key, '::') === false) {
            return $this->parseKey($key);
        }

        if (isset($this->keyParserCache[$key])) {
            return $this->keyParserCache[$key];
        }

        $segments = explode('.', $key);
        $parsed = $this->parseNamespacedSegments($key);
        return $this->keyParserCache[$key] = $parsed;
    }

    /**
     * Parse an array of namespaced segments.
     *
     * @param  string  $key
     * @return array
     */
    protected function parseNamespacedSegments($key)
    {
        list($namespace, $item) = explode('::', $key);

        // If the namespace is registered as a package, we will just assume the group
        // is equal to the namespace since all packages cascade in this way having
        // a single file per package, otherwise we'll just parse them as normal.
        if (in_array($namespace, $this->packages)) {
            return $this->parsePackageSegments($key, $namespace, $item);
        }

        return $this->keyParserParseSegments($key);
    }

    /**
     * Parse the segments of a package namespace.
     *
     * @param  string  $key
     * @param  string  $namespace
     * @param  string  $item
     * @return array
     */
    protected function parsePackageSegments($key, $namespace, $item)
    {
        $itemSegments = explode('.', $item);

        // If the configuration file doesn't exist for the given package group we can
        // assume that we should implicitly use the config file matching the name
        // of the namespace. Generally packages should use one type or another.
        if (!$this->loader->exists($itemSegments[0], $namespace)) {
            return [$namespace, 'config', $item];
        }

        return $this->keyParserParseSegments($key);
    }

    /**
     * Register a package for cascading configuration.
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @param  string  $namespace
     * @return void
     */
    public function package($namespace, $hint)
    {
        $this->packages[] = $namespace;

        // First we will simply register the namespace with the repository so that it
        // can be loaded. Once we have done that we'll register an after namespace
        // callback so that we can cascade an application package configuration.
        $this->addNamespace($namespace, $hint);

        $this->afterLoading($namespace, function($me, $group, $items) use ($namespace) {
            $env = $me->getEnvironment();

            $loader = $me->getLoader();

            return $loader->cascadePackage($env, $namespace, $group, $items);
        });
    }

    /**
     * Register an after load callback for a given namespace.
     *
     * @param  string   $namespace
     * @param  \Closure  $callback
     * @return void
     */
    public function afterLoading($namespace, Closure $callback)
    {
        $this->afterLoad[$namespace] = $callback;
    }

    /**
     * Get the collection identifier.
     *
     * @param  string  $group
     * @param  string  $namespace
     * @return string
     */
    protected function getCollection($group, $namespace = null)
    {
        $namespace = $namespace ?: '*';

        return $namespace.'::'.$group;
    }

    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        $this->loader->addNamespace($namespace, $hint);
    }

    /**
     * Returns all registered namespaces with the config
     * loader.
     *
     * @return array
     */
    public function getNamespaces()
    {
        return $this->loader->getNamespaces();
    }

    /**
     * Get the loader implementation.
     *
     * @return \October\Rain\Config\LoaderInterface
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * Set the loader implementation.
     *
     * @param  \October\Rain\Config\LoaderInterface  $loader
     * @return void
     */
    public function setLoader(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    /**
     * Get the current configuration environment.
     *
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Get the after load callback array.
     *
     * @return array
     */
    public function getAfterLoadCallbacks()
    {
        return $this->afterLoad;
    }

    /**
     * Get all of the configuration items.
     *
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Determine if the given configuration option exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Get a configuration option.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set a configuration option.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Unset a configuration option.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->set($key, null);
    }
}