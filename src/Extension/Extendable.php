<?php namespace October\Rain\Extension;

/**
 * Extendable class
 *
 * If a class extends this class, it will enable support for using "Private traits".
 *
 * Usage:
 *
 *     public $implement = [\Path\To\Some\Namespace\Class::class];
 *
 * See the `ExtensionBase` class for creating extension classes.
 *
 * @package october\extension
 * @author Alexey Bobkov, Samuel Georges
 */
class Extendable
{
    use ExtendableTrait;

    /**
     * @var array implement extensions for this class.
     */
    public $implement = [];

    /**
     * __construct the extendable class
     */
    public function __construct()
    {
        $this->extendableConstruct();
    }

    /**
     * __get an undefined property
     */
    public function __get($name)
    {
        return $this->extendableGet($name);
    }

    /**
     * __set an undefined property
     */
    public function __set($name, $value)
    {
        $this->extendableSet($name, $value);
    }

    /**
     * __call calls an undefined local method
     */
    public function __call($name, $params)
    {
        return $this->extendableCall($name, $params);
    }

    /**
     * __callStatic calls an undefined static method
     */
    public static function __callStatic($name, $params)
    {
        return self::extendableCallStatic($name, $params);
    }

    /**
     * __sleep prepare the object for serialization.
     */
    public function __sleep()
    {
        $this->extendableDestruct();

        return array_keys(get_object_vars($this));
    }

    /**
     * __wakeup when a model is being unserialized, check if it needs to be booted.
     */
    public function __wakeup()
    {
        $this->extendableConstruct();
    }

    /**
     * extend this class with a closure
     */
    public static function extend(callable $callback)
    {
        self::extendableExtendCallback($callback);
    }
}
