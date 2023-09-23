<?php namespace October\Rain\Argon;

use Carbon\Carbon as DateBase;

/**
 * Argon is an umbrella class for Carbon that automatically applies localizations
 *
 * @package october\argon
 * @author Alexey Bobkov, Samuel Georges
 */
class Argon extends DateBase
{
    /**
     * format
     */
    public function format($format)
    {
        return parent::translatedFormat($format);
    }

    /**
     * createFromFormat
     */
    public static function createFromFormat($format, $time, $timezone = null)
    {
        if (is_string($time)) {
            $time = static::translateTimeString($time, static::getLocale(), 'en');
        }

        return parent::rawCreateFromFormat($format, $time, $timezone);
    }

    /**
     * parse
     */
    public static function parse($time = null, $timezone = null)
    {
        if (is_string($time)) {
            $time = static::translateTimeString($time, static::getLocale(), 'en');
        }

        return parent::rawParse($time, $timezone);
    }
}
