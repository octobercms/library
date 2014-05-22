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
    protected $extensionData = [
        'extensions' => [],
        'methods' => [],
        'dynamicMethods' => []
    ];

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

    public static function extendableExtendCallback($callback)
    {
        $class = get_called_class();
        if (!isset(self::$extendableCallbacks[$class]) || !is_array(self::$extendableCallbacks[$class])) {
            self::$extendableCallbacks[$class] = [];
        }

        self::$extendableCallbacks[$class][] = $callback;
    }

    public function extendClassWith($extensionName)
    {
        if (!strlen($extensionName))
            return $this;

        if (isset($this->extensionData['extensions'][$extensionName]))
            throw new Exception(sprintf('Class %s has already been extended with %s', get_class($this), $extensionName));

        $this->extensionData['extensions'][$extensionName] = $extensionObject = new $extensionName($this);
        $this->extensionExtractMethods($extensionName, $extensionObject);
    }

    protected function extensionExtractMethods($extensionName, $extensionObject)
    {
        $extensionMethods = get_class_methods($extensionName);
        foreach ($extensionMethods as $methodName) {
            if ($methodName == '__construct' || $extensionObject->extensionIsHiddenMethod($methodName))
                continue;

            $this->extensionData['methods'][$methodName] = $extensionName;
        }
    }

    public function addDynamicMethod($extension, $dynamicName, $actualName)
    {
        $this->extensionData['dynamicMethods'][$dynamicName] = array($extension, $actualName);
    }

    public function isClassExtendedWith($name)
    {
        $name = str_replace('.', '\\', trim($name));
        return isset($this->extensionData['extensions'][$name]);
    }

    public function getClassExtension($name)
    {
        $name = str_replace('.', '\\', trim($name));
        return (isset($this->extensionData['extensions'][$name]))
            ? $this->extensionData['extensions'][$name]
            : null;
    }

    public function methodExists($name)
    {
        return (
            method_exists($this, $name) ||
            isset($this->extensionData['methods'][$name]) ||
            isset($this->extensionData['dynamicMethods'][$name])
        );
    }

    /**
     * Magic
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

    public function extendableSet($name, $value)
    {
        if (property_exists($this, $name))
            return $this->{$name} = $value;

        foreach ($this->extensionData['extensions'] as $extensionObject) {
            if (!isset($extensionObject->{$name}))
                continue;

            return $extensionObject->{$name} = $value;
        }

        $parent = get_parent_class();
        if ($parent !== false) {
            if (method_exists($parent, '__set'))
                return parent::__set($name, $value);

            return $parent->{$name} = $value;
        }
    }

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
            $extensionObject = $this->extensionData['dynamicMethods'][$name][0];
            $actualName = $this->extensionData['dynamicMethods'][$name][1];

            if (method_exists($extensionObject, $actualName) && is_callable([$extensionObject, $actualName]))
                return call_user_func_array(array($extensionObject, $actualName), $params);
        }

        $parent = get_parent_class();
        if ($parent !== false) {
            if (method_exists($parent, '__call'))
                return parent::__call($name, $params);

            return call_user_func_array(array($parent, $name), $params);
        }

        throw new Exception(sprintf('Class %s does not have a method definition for %s', get_class($this), $name));
    }

    private function extendableIsAccessible($class, $propertyName)
    {
        $reflector = new \ReflectionClass($class);
        $property = $reflector->getProperty($propertyName);
        return $property->isPublic();
    }
}