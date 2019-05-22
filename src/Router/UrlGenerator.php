<?php
namespace October\Rain\Router;

use Illuminate\Routing\UrlGenerator as UrlGeneratorBase;

// PECL HTTP constant definitions
if (!defined('HTTP_URL_REPLACE')) {
    define('HTTP_URL_REPLACE', 1);
}
if (!defined('HTTP_URL_JOIN_PATH')) {
    define('HTTP_URL_JOIN_PATH', 2);
}
if (!defined('HTTP_URL_JOIN_QUERY')) {
    define('HTTP_URL_JOIN_QUERY', 4);
}
if (!defined('HTTP_URL_STRIP_USER')) {
    define('HTTP_URL_STRIP_USER', 8);
}
if (!defined('HTTP_URL_STRIP_PASS')) {
    define('HTTP_URL_STRIP_PASS', 16);
}
if (!defined('HTTP_URL_STRIP_AUTH')) {
    define('HTTP_URL_STRIP_AUTH', 32);
}
if (!defined('HTTP_URL_STRIP_PORT')) {
    define('HTTP_URL_STRIP_PORT', 64);
}
if (!defined('HTTP_URL_STRIP_PATH')) {
    define('HTTP_URL_STRIP_PATH', 128);
}
if (!defined('HTTP_URL_STRIP_QUERY')) {
    define('HTTP_URL_STRIP_QUERY', 256);
}
if (!defined('HTTP_URL_STRIP_FRAGMENT')) {
    define('HTTP_URL_STRIP_FRAGMENT', 512);
}
if (!defined('HTTP_URL_STRIP_ALL')) {
    define('HTTP_URL_STRIP_ALL', 1024);
}

class UrlGenerator extends UrlGeneratorBase
{
    /**
     * Build a URL from an array.
     *
     * This function serves as a counterpart to the `parse_url` method available in PHP, and a userland implementation
     * of the `http_build_query` method provided by the PECL HTTP module. This allows a developer to parse a URL to an
     * array and make adjustments to the URL parts before combining them into a valid URL reference string.
     *
     * If PECL HTTP is installed, it will use the native method instead.
     *
     * @param array $url The URL parts, as an array. Must match the structure returned from a `parse_url` call.
     * @param array $parts The URL replacement parts. Allows a developer to replace certain sections of the URL with
     *                     a different value.
     * @param mixed $flags A bitmask of binary or'ed HTTP_URL constants. By default, this is set to HTTP_URL_REPLACE.
     * @param array $newUrl If set, this will be filled with the array parts of the composed URL, similar to the return
     *                      value of `parse_url`.
     *
     * @return string
     */
    public function buildUrl(array $url, array $parts = [], $flags = HTTP_URL_REPLACE, &$newUrl = []): string
    {
        if (function_exists('http_build_url')) {
            return http_build_url($url, $parts, $flags, $newUrl);
        }


    }
}
