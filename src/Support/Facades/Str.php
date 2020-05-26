<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * @method static string after(string $subject, string $search)
 * @method static string ascii(string $value, string $language = 'en')
 * @method static string before(string $subject, string $search)
 * @method static string camel(string $value)
 * @method static bool contains(string $haystack, string|array $needles)
 * @method static bool endsWith(string $haystack, string|array $needles)
 * @method static string finish(string $value, string $cap)
 * @method static bool is(string|array $pattern, string $value)
 * @method static string kebab(string $value)
 * @method static int length(string $value, string $encoding = null)
 * @method static string limit(string $value, int $limit = 100, string $end = '...')
 * @method static string lower(string $value)
 * @method static string words(string $value, int $words = 100, $end = '...')
 * @method static array parseCallback(string $callback, string $default = null)
 * @method static string plural(string $value, int $count)
 * @method static string random(int $length = 16)
 * @method static string replaceArray(string $search, array $replace, string $subject)
 * @method static string replaceFirst(string $search, string $replace, string $subject)
 * @method static string replaceLast(string $search, string $replace, string $subject)
 * @method static string start(string $value, string $prefix)
 * @method static string upper(string $value)
 * @method static string title(string $value)
 * @method static string singular(string $value)
 * @method static string slug(string $title, string $seperator = '-', string $language = 'en')
 * @method static string snake(string $value, string $delimiter = '_')
 * @method static bool startsWith(string $haystack, string|array $needles)
 * @method static string studly(string $value)
 * @method static string substr(string $string, int $start, int $length = null)
 * @method static string ucfirst(string $string)
 * @method static string ordinal(int $number)
 * @method static string normalizeEol(string $string)
 * @method static string normalizeClassName(string $name)
 * @method static string getClassId(string $name)
 * @method static string getClassNamespace(string $name)
 * @method static int getPrecedingSymbols(string $string, string $symbol)
 *
 * @see \October\Rain\Support\Str
 */
class Str extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'string';
    }
}
