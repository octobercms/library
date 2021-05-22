<?php namespace October\Rain\Support\Traits;

/**
 * Singleton trait allows a simple interface for treating a class as a singleton
 * Usage: myObject::instance()
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
trait Singleton
{
    /**
     * @var self instance
     */
    protected static $instance;

    /**
     * instance create a new instance of this singleton
     */
    final public static function instance()
    {
        return isset(static::$instance)
            ? static::$instance
            : static::$instance = new static;
    }

    /**
     * forgetInstance if it exists
     */
    final public static function forgetInstance()
    {
        static::$instance = null;
    }

    /**
     * __construct
     */
    final protected function __construct()
    {
        $this->init();
    }

    /**
     * init the singleton free from constructor parameters
     */
    protected function init()
    {
    }

    /**
     * __clone
     * @ignore
     */
    public function __clone()
    {
        trigger_error('Cloning '.__CLASS__.' is not allowed.', E_USER_ERROR);
    }

    /**
     * __wakeup
     * @ignore
     */
    public function __wakeup()
    {
        trigger_error('Unserializing '.__CLASS__.' is not allowed.', E_USER_ERROR);
    }
}
