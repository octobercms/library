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
     * Converts a string to a boolean.
     */
    public static function toBoolean($string)
    {
        return self::evalBoolean($string) === true;
    }

    /**
     * Checks if a string is a boolean and returns it, otherwise the plain string is returned.
     * True values: y, yes, true.
     * False values: n, no, false.
     */
    public static function evalBoolean($string)
    {
        switch (strtolower(trim($string))) {
            case 'y':
            case 'yes':
            case 'true':
                return true;

            case 'n':
            case 'no':
            case 'false':
                return false;

            default:
                return $string;
        }
    }

    /**
     * Converts a HTML array string to a PHP array. Empty values are removed.
     * HTML: user[location][city]
     * PHP:  ['user', 'location', 'city']
     * @param $string String to process
     * @return array
     */
    public static function evalHtmlArray($string)
    {
        $result = [$string];

        if (preg_match('/^([^\]]+)(?:\[(.+)\])+$/', $string, $matches)) {
            if (count($matches) < 2)
                return $result;

            $result = explode('][', $matches[2]);
            array_unshift($result, $matches[1]);
        }

        return array_filter($result);
    }

    /**
     * Removes HTML from a string
     * @param $string String to strip HTML from
     * @return string
     */
    public static function stripHtml($string)
    {
        return htmlspecialchars_decode(strip_tags($string));
    }

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
     * Obtains an object class name without namespaces
     */
    public static function getRealClass($name) 
    {
        $name = static::normalizeClassName($name);

        if (preg_match('@\\\\([\w]+)$@', $name, $matches))
            $name = $matches[1];

        return $name;
    }
}