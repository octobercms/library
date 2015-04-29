<?php namespace October\Rain\Html;

use Html; // For @deprecate Remove if year >= 2016

/**
 * Methods that may be useful for processing HTML tasks
 *
 * @package october\html
 * @author Alexey Bobkov, Samuel Georges
 */
class Helper
{

    /**
     * Converts a HTML array string to an identifier string.
     * HTML: user[location][city]
     * Result: user-location-city
     * @param $string String to process
     * @return string
     */
    public static function nameToId($string)
    {
        return rtrim(str_replace('--', '-', str_replace(['[', ']'], '-', $string)), '-');
    }

    /**
     * Converts a HTML named array string to a PHP array. Empty values are removed.
     * HTML: user[location][city]
     * PHP:  ['user', 'location', 'city']
     * @param $string String to process
     * @return array
     */
    public static function nameToArray($string)
    {
        $result = [$string];

        if (strpbrk($string, '[]') === false)
            return $result;

        if (preg_match('/^([^\]]+)(?:\[(.+)\])+$/', $string, $matches)) {
            if (count($matches) < 2)
                return $result;

            $result = explode('][', $matches[2]);
            array_unshift($result, $matches[1]);
        }

        return array_filter($result);
    }

    /**
     * @deprecated Moved to Html::strip
     */
    public static function strip($string)
    {
        traceLog('HtmlHelper::strip has been deprecated, use Html::strip instead.');
        return Html::strip($string);
    }

    /**
     * @deprecated Moved to Html::limit
     */
    public static function limit($html, $maxLength, $end = '...')
    {
        traceLog('HtmlHelper::limit has been deprecated, use Html::limit instead.');
        return Html::limit($string, $maxLength, $end);
    }

    /**
     * @deprecated Moved to Html::clean
     */
    public static function clean($html)
    {
        traceLog('HtmlHelper::clean has been deprecated, use Html::clean instead.');
        return Html::clean($string);
    }
}
