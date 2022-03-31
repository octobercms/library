<?php namespace October\Rain\Element;

use Illuminate\Support\Fluent;

/**
 * ElementBase class for all elements
 *
 * @package october\element
 * @author Alexey Bobkov, Samuel Georges
 */
abstract class ElementBase extends Fluent
{
    use \October\Rain\Extension\ExtendableTrait;

    /**
     * __construct
     */
    public function __construct(array $attributes = [])
    {
        $this->initDefaultValues();

        parent::__construct($attributes);

        $this->extendableConstruct();
    }

    /**
     * useConfig
     */
    public function useConfig(array $config): ElementBase
    {
        $this->attributes = array_merge($this->attributes, $config);

        return $this;
    }

    /**
     * initDefaultValues for this element
     */
    protected function initDefaultValues()
    {
    }

    /**
     * extend this object properties upon construction.
     */
    public static function extend(callable $callback)
    {
        self::extendableExtendCallback($callback);
    }

    /**
     * __get
     */
    public function __get($name)
    {
        return $this->extendableGet($name);
    }

    /**
     * __set
     */
    public function __set($name, $value)
    {
        return $this->extendableSet($name, $value);
    }

    /**
     * __call
     */
    public function __call($name, $params)
    {
        return $this->extendableCall($name, $params);
    }
}
