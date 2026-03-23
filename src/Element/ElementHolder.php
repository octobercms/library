<?php namespace October\Rain\Element;

use October\Rain\Support\Collection;
use IteratorAggregate;
use Traversable;

/**
 * ElementHolder is a general collection used when passing a group of elements by reference,
 * allowing access via array and object notation.
 *
 * @package october\element
 * @author Alexey Bobkov, Samuel Georges
 */
class ElementHolder extends ElementBase implements IteratorAggregate
{
    /**
     * @var array touchedElements is used by getTouchedElements
     */
    protected $touchedElements = [];

    /**
     * getTouchedElements return field names that have been accessed
     */
    public function getTouchedElements(): array
    {
        return $this->touchedElements;
    }

    /**
     * get an element from the holder instance.
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (isset($this->touchedElements[$key])) {
            return $this->touchedElements[$key];
        }

        if (isset($this->config[$key])) {
            return $this->touchedElements[$key] = $this->config[$key];
        }

        return parent::get($key, $default);
    }

    /**
     * __get dynamically retrieves the value of an attribute, a safe object
     * is returned for missing keys to avoid indirect modification errors.
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        $result = $this->get($key);

        if ($result === null) {
            return new class {
                public function __get($k) { return $this; }
                public function __set($k, $v) {}
            };
        }

        return $result;
    }

    /**
     * getIterator for the elements.
     */
    public function getIterator(): Traversable
    {
        return new Collection($this->config);
    }
}
