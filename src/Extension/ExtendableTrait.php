<?php namespace October\Rain\Extension;

use Exception;

/**
 * Extension trait
 * Allows for "Private traits"
 *
 * @package october\extension
 * @author Alexey Bobkov, Samuel Georges
 */

trait ExtendableTrait
{

    /**
     * @var array Class reflection information, including behaviors.
     */
    protected $extensionData = [
        'extensions'     => [],
        'methods'        => [],
        'dynamicMethods' => []
    ];

    /**
     * @var array Used to extend the constructor of an extendable class.
     * Eg: Class::extend(function($obj) { })
     */
    protected static $extendableCallbacks = [];

    /**
     * Constructor.
     */
    public function extendableConstruct()
    {
        /*
         * Apply init callbacks
         */
        $classes = array_merge([get_class($this)], class_parents($this));
        foreach ($classes as $class) {
            if (isset(self::$extendableCallbacks[$class]) && is_array(self::$extendableCallbacks[$class])) {
                foreach (self::$extendableCallbacks[$class] as $callback) {
                    $callback($this);
                }
            }
        }

        /*
         * Apply extensions
         */
        if (!$this->implement)
            return;

        if (is_string($this->implement))
            $uses = explode(',', $this->implement);
        elseif (is_array($this->implement))
            $uses = $this->implement;
        else
            throw new Exception(sprintf('Class %s contains an invalid $implement value', get_class($this)));

        foreach ($uses as $use) {
            $useClass = str_replace('.', '\\', trim($use));
            $this->extendClassWith($useClass);
        }
    }

    /**
     * Helper method for ::extend() static method
     * @param  callable $callback
     * @return void
     */
    public static function extendableExtendCallback($callback)
    {
        $class = get_called_class();
        if (!isset(self::$extendableCallbacks[$class]) || !is_array(self::$extendableCallbacks[$class])) {
            self::$extendableCallbacks[$class] = [];
        }

        self::$extendableCallbacks[$class][] = $callback;
    }

    /**
     * Extracts the available methods from a behavior and adds it to the
     * list of callable methods.
     * @param  string $extensionName
     * @param  object $extensionObject
     * @return void
     */
    protected function extensionExtractMethods($extensionName, $extensionObject)
    {
        $extensionMethods = get_class_methods($extensionName);
        foreach ($extensionMethods as $methodName) {
            if ($methodName == '__construct' || $extensionObject->extensionIsHiddenMethod($methodName))
                continue;

            $this->extensionData['methods'][$methodName] = $extensionName;
        }
    }

    /**
     * Programatically adds a method to the extendable class
     * @param string   $dynamicName
     * @param callable $methodName
     * @param string   $extension
     */
    public function addDynamicMethod($dynamicName, $method, $extension = null)
    {
        if (is_string($method) && $extension && ($extensionObj = $this->getClassExtension($extension))) {
            $method = array($extensionObj, $method);
        }

        $this->extensionData['dynamicMethods'][$dynamicName] = $method;
    }

    /**
     * Dynamically extend a class with a specified behavior
     * @param  string $extensionName
     * @return void
     */
    public function extendClassWith($extensionName)
    {
        if (!strlen($extensionName))
            return $this;

        if (isset($this->extensionData['extensions'][$extensionName]))
            throw new Exception(sprintf('Class %s has already been extended with %s', get_class($this), $extensionName));

        $this->extensionData['extensions'][$extensionName] = $extensionObject = new $extensionName($this);
        $this->extensionExtractMethods($extensionName, $extensionObject);
    }

    /**
     * Check if extendable class is extended with a behavior object
     * @param  string $name Fully qualified behavior name
     * @return boolean
     */
    public function isClassExtendedWith($name)
    {
        $name = str_replace('.', '\\', trim($name));
        return isset($this->extensionData['extensions'][$name]);
    }

    /**
     * Returns a behavior object from an extendable class, example:
     *
     *   $this->getClassExtension('Backend.Behaviors.FormController')
     *
     * @param  string $name Fully qualified behavior name
     * @return mixed
     */
    public function getClassExtension($name)
    {
        $name = str_replace('.', '\\', trim($name));
        return (isset($this->extensionData['extensions'][$name]))
            ? $this->extensionData['extensions'][$name]
            : null;
    }

    /**
     * Short hand for getClassExtension() method, except takes the short
     * extension name, example:
     *
     *   $this->asExtension('FormController')
     *
     * @param  string $shortName
     * @return mixed
     */
    public function asExtension($shortName)
    {
        $hints = [];
        foreach ($this->extensionData['extensions'] as $class => $obj) {
            if (preg_match('@\\\\([\w]+)$@', $class, $matches) && $matches[1] == $shortName)
                return $obj;
        }
    }

    /**
     * Checks if a method exists, extension equivalent of method_exists()
     * @param  mixed  $class
     * @param  string $propertyName
     * @return boolean
     */
    public function methodExists($name)
    {
        return (
            method_exists($this, $name) ||
            isset($this->extensionData['methods'][$name]) ||
            isset($this->extensionData['dynamicMethods'][$name])
        );
    }

    /**
     * Checks if a property is accessible, property equivalent of is_callabe()
     * @param  mixed  $class
     * @param  string $propertyName
     * @return boolean
     */
    protected function extendableIsAccessible($class, $propertyName)
    {
        $reflector = new \ReflectionClass($class);
        $property = $reflector->getProperty($propertyName);
        return $property->isPublic();
    }

    /**
     * Magic method for __get()
     * @param  string $name
     * @return string
     */
    public function extendableGet($name)
    {
        if (property_exists($this, $name))
            return $this->{$name};

        foreach ($this->extensionData['extensions'] as $extensionObject) {
            if (property_exists($extensionObject, $name) && $this->extendableIsAccessible($extensionObject, $name))
                return $extensionObject->{$name};
        }

        $parent = get_parent_class();
        if ($parent !== false) {
            if (method_exists($parent, '__get'))
                return parent::__get($name);

            return $parent->{$name};
        }
    }

    /**
     * Magic method for __set()
     * @param  string $name
     * @param  string $value
     * @return string
     */
    public function extendableSet($name, $value)
    {
        if (property_exists($this, $name))
            return $this->{$name} = $value;

        foreach ($this->extensionData['extensions'] as $extensionObject) {
            if (!property_exists($extensionObject, $name))
                continue;

            return $extensionObject->{$name} = $value;
        }

        /*
         * This targets trait usage in particular
         */
        $parent = get_parent_class();
        if ($parent !== false) {
            if (method_exists($parent, '__set'))
                return parent::__set($name, $value);

            return $parent->{$name} = $value;
        }

        return $this->{$name} = $value;
    }

    /**
     * Magic method for __call()
     * @param  string $name
     * @param  array  $params
     * @return mixed
     */
    public function extendableCall($name, $params = null)
    {
        if (method_exists($this, $name))
            return call_user_func_array(array($this, $name), $params);

        if (isset($this->extensionData['methods'][$name])) {
            $extension = $this->extensionData['methods'][$name];
            $extensionObject = $this->extensionData['extensions'][$extension];

            if (method_exists($extension, $name) && is_callable([$extension, $name]))
                return call_user_func_array(array($extensionObject, $name), $params);
        }

        if (isset($this->extensionData['dynamicMethods'][$name])) {
            $dynamicCallable = $this->extensionData['dynamicMethods'][$name];

            if (is_callable($dynamicCallable))
                return call_user_func_array($dynamicCallable, $params);
        }

        $parent = get_parent_class();
        if ($parent !== false) {
            if (method_exists($parent, '__call'))
                return parent::__call($name, $params);

            return call_user_func_array(array($parent, $name), $params);
        }

        throw new Exception(sprintf('Class %s does not have a method definition for %s', get_class($this), $name));
    }

}