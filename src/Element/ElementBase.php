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
    public function __construct($config = [])
    {
        $this->initDefaultValues();

        $this->useConfig($config);
    }

    /**
     * initDefaultValues override method
     */
    protected function initDefaultValues()
    {
    }

    /**
     * evalConfig override method
     */
    public function evalConfig(array $config)
    {
    }

    /**
     * useConfig is used internally
     */
    public function useConfig(array $config): ElementBase
    {
        $this->config = array_merge($this->config, $config);

        $this->evalConfig($config);

        return $this;
    }

    /**
     * getConfig returns the entire config array
     */
    public function getConfig($key = null, $default = null)
    {
        if ($key !== null) {
            return $this->get($key, $default);
        }

        return $this->config;
    }

    /**
     * get an attribute from the element instance.
     * @param  string  $key
     */
    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }

        return value($default);
    }

    /**
     * toArray converts the element instance to an array.
     * @return array
     */
    public function toArray()
    {
        return $this->config;
    }

    /**
     * jsonSerialize converts the object into something JSON serializable.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * toJson converts the element instance to JSON.
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * offsetExists determines if the given offset exists.
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->config[$offset]);
    }

    /**
     * offsetGet gets the value for a given offset.
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * offsetSet sets the value at the given offset.
     * @param  string  $offset
     * @param  mixed  $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->config[$offset] = $value;
    }

    /**
     * offsetUnset unsets the value at the given offset.
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->config[$offset]);
    }

    /**
     * __call handles dynamic calls to the element instance to set config.
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
     * __get dynamically retrieves the value of an attribute.
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * __set dynamically sets the value of an attribute.
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
     * __isset dynamically checks if an attribute is set.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * __unset dynamically unsets an attribute.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        $this->offsetUnset($key);
    }
}
