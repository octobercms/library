<?php namespace October\Rain\Support;

use Illuminate\Support\Str as StrHelper;

/**
 * String helper
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class Str extends StrHelper
{
    /**
     * Converts line breaks to a standard \r\n pattern.
     */
    public static function normalizeEol($string)
    {
        return preg_replace('~\R~u', "\r\n", $string);
    }

    /**
     * Removes the starting slash from a class namespace \
     */
    public static function normalizeClassName($name)
    {
        if (is_object($name))
            $name = get_class($name);

        $name = '\\'.ltrim($name, '\\');
        return $name;
    }

    /**
     * Generates a class ID from either an object or a string of the class name.
     */
    public static function getClassId($name)
    {
        if (is_object($name))
            $name = get_class($name);

        $name = ltrim($name, '\\');
        $name = str_replace('\\', '_', $name);

        return strtolower($name);
    }

    /**
     * Returns a class namespace
     */
    public static function getClassNamespace($name)
    {
        $name = static::normalizeClassName($name);
        return substr($name, 0, strrpos($name, "\\"));
    }

}