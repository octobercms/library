<?php namespace October\Rain\Support;

use App; // @todo Allow external binding

/**
 * IoC Singleton class.
 *
 * A self binding, self contained single class that supports IoC.
 * Usage: myObject::instance()
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class Singleton
{
    /**
     * Create a new instance of this singleton.
     */
    final public static function instance()
    {
        $accessor = static::getSingletonAccessor();

        if (!App::bound($accessor)) {
            App::singleton($accessor, function () {
                return static::getSingletonInstance();
            });
        }

        return App::make($accessor);
    }

    /**
     * This should be a meaningful IoC container code. Eg: backend.helper
     */
    protected static function getSingletonAccessor()
    {
        return get_called_class();
    }

    final public static function getSingletonInstance()
    {
        return new static;
    }

    /**
     * Constructor.
     */
    final protected function __construct()
    {
        $this->init();
    }

    /**
     * Initialize the singleton free from constructor parameters.
     */
    protected function init()
    {
    }

    /**
     * @ignore
     */
    public function __clone()
    {
        trigger_error('Cloning '.__CLASS__.' is not allowed.', E_USER_ERROR);
    }

    /**
     * @ignore
     */
    public function __wakeup()
    {
        trigger_error('Unserializing '.__CLASS__.' is not allowed.', E_USER_ERROR);
    }
}
