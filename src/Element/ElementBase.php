<?php namespace October\Rain\Element;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use October\Rain\Extension\Extendable;
use JsonSerializable;
use ArrayAccess;

/**
 * ElementBase class for all elements
 *
 * @package october\element
 * @author Alexey Bobkov, Samuel Georges
 */
abstract class ElementBase extends Extendable implements Arrayable, ArrayAccess, Jsonable, JsonSerializable
{
    /**
     * @var array config values for this instance
     */
    public $config = [];

    /**
     * __construct
     */
    public function __construct(array $config = [])
    {
        $this->initDefaultValues();

        $this->useConfig($config);
    }

    /**
     * useConfig
     */
    public function useConfig(array $config): ElementBase
    {
        $this->config = array_merge($this->config, $config);

        $this->evalConfig($config);

        return $this;
    }

    /**
     * evalConfig override
     */
    protected function evalConfig(array $config)
    {
    }

    /**
     * getConfig returns a raw config item value, or the entire array.
     * @param  string $value
     * @param  string $default
     * @return mixed
     */
    public function getConfig($value = null, $default = null)
    {
        if ($value === null) {
            return $this->config;
        }

        return array_get($this->config, $value, $default);
    }

    /**
     * initDefaultValues for this element
     */
    protected function initDefaultValues()
    {
    }

    /**
     * Get an attribute from the element instance.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }

        return value($default);
    }

    /**
     * Convert the element instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->config;
    }

    /**
     * Convert the object into something JSON serializable.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the element instance to JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Determine if the given offset exists.
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->config[$offset]);
    }

    /**
     * Get the value for a given offset.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Set the value at the given offset.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        $this->config[$offset] = $value;
    }

    /**
     * Unset the value at the given offset.
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->config[$offset]);
    }

    /**
     * Handle dynamic calls to the element instance to set config.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return $this
     */
    public function __call($method, $parameters)
    {
        $this->config[$method] = count($parameters) > 0 ? $parameters[0] : true;

        return $this;
    }

    /**
     * Dynamically retrieve the value of an attribute.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Dynamically set the value of an attribute.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Dynamically check if an attribute is set.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Dynamically unset an attribute.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }
}
