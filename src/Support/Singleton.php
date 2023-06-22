<?php namespace October\Rain\Support;

use App;

/**
 * @deprecated use App::singleton() with a manually built class
 */
class Singleton
{
    /**
     * __construct
     */
    final protected function __construct()
    {
        $this->init();
    }

    /**
     * instance creates a new instance of this singleton
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
     * getSingletonAccessor should return a meaningful IoC container code.
     * Eg: backend.helper
     */
    protected static function getSingletonAccessor()
    {
        return get_called_class();
    }

    /**
     * getSingletonInstance returns the final instance of this singleton
     */
    final public static function getSingletonInstance()
    {
        return new static;
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
