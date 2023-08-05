<?php namespace October\Rain\Extension;

/**
 * Container holds constructor logic for all extensions
 *
 * @package october\extension
 * @author Alexey Bobkov, Samuel Georges
 */
class Container
{
    /**
     * @var array classCallbacks is used to extend the constructor of an extendable class. Eg:
     *
     *     Class::extend(function($obj) { })
     *
     */
    public static $classCallbacks = [];

    /**
     * @var array Used to extend the constructor of an extension class. Eg:
     *
     *     BehaviorClass::extend(function($obj) { })
     *
     */
    public static $extensionCallbacks = [];

    /**
     * extendClass extends a class without including it
     */
    public static function extendClass(string $class, callable $callback)
    {
        self::$classCallbacks[$class][] = $callback;
    }

    /**
     * extendBehavior extends a class without including it
     */
    public static function extendBehavior(string $class, callable $callback)
    {
        self::$extensionCallbacks[$class][] = $callback;
    }

    /**
     * clearExtensions clears the list of extended classes so they will be re-extended
     */
    public static function clearExtensions()
    {
        self::$classCallbacks = [];
        self::$extensionCallbacks = [];
    }
}
