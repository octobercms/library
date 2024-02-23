<?php namespace October\Rain\Support;

use Illuminate\Support\Str as StrHelper;
use voku\helper\ASCII;

/**
 * Str helper
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class Str extends StrHelper
{
    /**
     * slug adds extra sugar to convert slashes to separators
     *
     * @param  string  $title
     * @param  string  $separator
     * @param  string|null  $language
     * @param  array<string, string>  $dictionary
     * @return string
     */
    public static function slug($title, $separator = '-', $language = 'en', $dictionary = ['@' => 'at'])
    {
        $title = str_replace(['\\', '/'], ' ', (string) $title);

        return parent::slug($title, $separator, $language, $dictionary);
    }
    /**
     * ascii applies transliterate when the language is not found
     *
     * @param  string  $value
     * @param  string  $language
     * @return string
     */
    public static function ascii($value, $language = 'en')
    {
        return ASCII::to_ascii((string) $value, $language, true, false, true);
    }

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

        return ltrim($name, '\\');
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

    /**
     * limitMiddle limits the length of a string by removing characters from the middle
     *
     * @param  string  $value
     * @param  int  $limit
     * @param  string  $marker
     * @return string
     */
    public static function limitMiddle($value, $limit = 100, $marker = '...')
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        if ($limit > 3) {
            $limit -= 3;
        }

        $limitStart = floor($limit / 2);
        $limitEnd = $limit - $limitStart;

        $valueStart = rtrim(mb_strimwidth($value, 0, $limitStart, '', 'UTF-8'));
        $valueEnd = ltrim(mb_strimwidth($value, $limitEnd * -1, $limitEnd, '', 'UTF-8'));

        return $valueStart . $marker . $valueEnd;
    }
}
