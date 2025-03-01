<?php namespace October\Rain\Support;

use Carbon\Carbon as DateBase;

/**
 * Date is an umbrella class for Carbon that automatically applies localizations
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class Date extends DateBase
{
    /**
     * format
     */
    public function format(string $format): string
    {
        return parent::translatedFormat($format);
    }

    /**
     * createFromFormat
     */
    public static function createFromFormat($format, $time, $timezone = null): ?DateBase
    {
        if (is_string($time)) {
            $time = static::translateTimeString($time, static::getLocale(), 'en');
        }

        return parent::rawCreateFromFormat($format, $time, $timezone);
    }

    /**
     * parse
     */
    public static function parse($time = null, $timezone = null): static
    {
        if (is_string($time)) {
            $time = static::translateTimeString($time, static::getLocale(), 'en');
        }

        return parent::rawParse($time, $timezone);
    }
}
