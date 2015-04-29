<?php namespace October\Rain\Support;

use Illuminate\Support\Str as StrHelper;
use October\Rain\Html\Helper as HtmlHelper; // For @deprecate Remove if year >= 2016
use Html; // For @deprecate Remove if year >= 2016

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

    //
    // Deprecated -- Remove this section if year >= 2016
    //

    /**
     * @deprecated Moved to October\Rain\Html\Helper::nameToId
     */
    public static function evalHtmlId($string)
    {
        traceLog('Str::evalHtmlId has been deprecated, use October\Rain\Html\Helper::nameToId instead.');
        return HtmlHelper::nameToId($string);
    }

    /**
     * @deprecated Moved to October\Rain\Html\Helper::nameToArray
     */
    public static function evalHtmlArray($string)
    {
        traceLog('Str::evalHtmlArray has been deprecated, use October\Rain\Html\Helper::nameToArray instead.');
        return HtmlHelper::nameToArray($string);
    }

    /**
     * @deprecated Moved to October\Rain\Html\Helper::strip
     */
    public static function stripHtml($string)
    {
        traceLog('Str::stripHtml has been deprecated, use Html::strip instead.');
        return Html::strip($string);
    }

    /**
     * @deprecated Moved to October\Rain\Html\Helper::limit
     */
    public static function limitHtml($string, $maxLength, $end = '...')
    {
        traceLog('Str::limitHtml has been deprecated, use Html::limit instead.');
        return Html::limit($string, $maxLength, $end);
    }

    /**
     * @deprecated Moved to October\Rain\Html\Helper::clean
     */
    public static function cleanHtml($string)
    {
        traceLog('Str::cleanHtml has been deprecated, use Html::clean instead.');
        return Html::clean($string);
    }

}