<?php namespace October\Rain\Support;

use Illuminate\Support\Str as StrHelper;

/**
 * Str helper
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class Str extends StrHelper
{
    /**
     * ordinal converts number to its ordinal English form
     *
     * This method converts 13 to 13th, 2 to 2nd ...
     *
     * @param integer $number Number to get its ordinal value
     * @return string Ordinal representation of given string.
     */
    public static function ordinal($number)
    {
        if (in_array($number % 100, range(11, 13))) {
            return $number.'th';
        }

        switch ($number % 10) {
            case 1:
                return $number.'st';
            case 2:
                return $number.'nd';
            case 3:
                return $number.'rd';
            default:
                return $number.'th';
        }
    }

    /**
     * normalizeEol converts line breaks to a standard \r\n pattern
     */
    public static function normalizeEol($string)
    {
        return preg_replace('~\R~u', "\r\n", $string);
    }

    /**
     * normalizeClassName removes the starting slash from a class namespace \
     */
    public static function normalizeClassName($name)
    {
        if (is_object($name)) {
            $name = get_class($name);
        }

        $name = '\\'.ltrim($name, '\\');
        return $name;
    }

    /**
     * getClassId generates a class ID from either an object or a string of the class name
     */
    public static function getClassId($name)
    {
        if (is_object($name)) {
            $name = get_class($name);
        }

        $name = ltrim($name, '\\');
        $name = str_replace('\\', '_', $name);

        return strtolower($name);
    }

    /**
     * getClassNamespace returns a class namespace
     */
    public static function getClassNamespace($name)
    {
        $name = static::normalizeClassName($name);
        return substr($name, 0, strrpos($name, "\\"));
    }

    /**
     * getPrecedingSymbols checks if $string begins with any number of consecutive symbols,
     * returns the number, otherwise returns 0
     */
    public static function getPrecedingSymbols(string $string, string $symbol): int
    {
        return strlen($string) - strlen(ltrim($string, $symbol));
    }
}
