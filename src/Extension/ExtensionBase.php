<?php namespace October\Rain\Extension;

/**
 * Extension class
 * Allows for "Private traits"
 *
 * @package october\extension
 * @author Alexey Bobkov, Samuel Georges
 */

class ExtensionBase
{
    use ExtensionTrait;

    public static function extend(callable $callback)
    {
        self::extensionExtendCallback($callback);
    }
}