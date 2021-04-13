<?php namespace October\Rain\Extension;

/**
 * ExtensionBase allows for "private traits"
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
