<?php namespace October\Rain\Extension;

/**
 * ExtensionTrait allows for "private traits"
 *
 * @package october\extension
 * @see October\Rain\Extension\ExtensionBase
 * @author Alexey Bobkov, Samuel Georges
 */
trait ExtensionTrait
{
    /**
     * @var string extendableStaticCalledClass is the calling class when using a static method.
     */
    public static $extendableStaticCalledClass = null;

    /**
     * @var array extensionHidden are properties and methods that cannot be accessed.
     */
    protected $extensionHidden = [
        'fields' => [],
        'methods' => ['extensionIsHiddenField', 'extensionIsHiddenMethod']
    ];

    /**
     * extensionApplyInitCallbacks
     */
    public function extensionApplyInitCallbacks()
    {
        $classes = array_merge([get_class($this)], class_parents($this));
        foreach ($classes as $class) {
            if (isset(Container::$extensionCallbacks[$class]) && is_array(Container::$extensionCallbacks[$class])) {
                foreach (Container::$extensionCallbacks[$class] as $callback) {
                    call_user_func($callback, $this);
                }
            }
        }
    }

    /**
     * extensionExtendCallback is a helper method for `::extend()` static method
     * @param  callable $callback
     * @return void
     */
    public static function extensionExtendCallback($callback)
    {
        $class = get_called_class();
        if (
            !isset(Container::$extensionCallbacks[$class]) ||
            !is_array(Container::$extensionCallbacks[$class])
        ) {
            Container::$extensionCallbacks[$class] = [];
        }

        Container::$extensionCallbacks[$class][] = $callback;
    }

    /**
     * extensionHideField
     */
    protected function extensionHideField($name)
    {
        $this->extensionHidden['fields'][] = $name;
    }

    /**
     * extensionHideMethod
     */
    protected function extensionHideMethod($name)
    {
        $this->extensionHidden['methods'][] = $name;
    }

    /**
     * extensionIsHiddenField
     */
    public function extensionIsHiddenField($name)
    {
        return in_array($name, $this->extensionHidden['fields']);
    }

    /**
     * extensionIsHiddenMethod
     */
    public function extensionIsHiddenMethod($name)
    {
        return in_array($name, $this->extensionHidden['methods']);
    }

    /**
     * getCalledExtensionClass
     */
    public static function getCalledExtensionClass()
    {
        return self::$extendableStaticCalledClass;
    }
}
