<?php namespace October\Rain\Extension;

/**
 * Extension class
 *
 * If a class extends this class, it will enable support for using "Private traits".
 * Usage:
 *   public $implement = ['Path.To.Some.Namespace.Class'];
 *
 * See the ExtensionBase class for creating extension classes.
 *
 * @package october\extension
 * @author Alexey Bobkov, Samuel Georges
 */

class Extendable
{
    use ExtendableTrait;

    public $implement;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->extendableConstruct();
    }

    public function __get($name)
    {
        return $this->extendableGet($name);
    }

    public function __set($name, $value)
    {
        return $this->extendableSet($name, $value);
    }

    public function __call($name, $params = null)
    {
        return $this->extendableCall($name, $params);
    }
}