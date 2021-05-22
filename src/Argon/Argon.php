<?php namespace October\Rain\Argon;

use Carbon\Carbon as DateBase;

/**
 * Argon is an umbrella class for Carbon
 *
 * @package october\argon
 * @author Alexey Bobkov, Samuel Georges
 */
class Argon extends DateBase
{
    /**
     * @var string|callable|null formatFunction function to call instead of format
     */
    protected static $formatFunction = 'translatedFormat';

    /**
     * @var string|callable|null createFromFormatFunction function to call instead
     * of createFromFormat
     */
    protected static $createFromFormatFunction = 'createFromFormatWithCurrentLocale';

    /**
     * @var string|callable|null parseFunction function to call instead of parse.
     */
    protected static $parseFunction = 'parseWithCurrentLocale';

    /**
     * parseWithCurrentLocale
     */
    public static function parseWithCurrentLocale($time = null, $timezone = null)
    {
        if (is_string($time)) {
            $time = static::translateTimeString($time, static::getLocale(), 'en');
        }

        return parent::rawParse($time, $timezone);
    }

    /**
     * createFromFormatWithCurrentLocale
     */
    public static function createFromFormatWithCurrentLocale($format, $time = null, $timezone = null)
    {
        if (is_string($time)) {
            $time = static::translateTimeString($time, static::getLocale(), 'en');
        }

        return parent::rawCreateFromFormat($format, $time, $timezone);
    }

    /**
     * getLanguageFromLocale gets the language portion of the locale.
     * @param string $locale
     * @return string
     */
    public static function getLanguageFromLocale($locale)
    {
        $parts = explode('_', str_replace('-', '_', $locale));

        return $parts[0];
    }
}
