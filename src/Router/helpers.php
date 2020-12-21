<?php

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

if (!function_exists('http_build_url')) {
    /**
     * Polyfill for `http_build_url` method provided by PECL HTTP extension.
     *
     * @see \October\Rain\Router\UrlGenerator::buildUrl()
     * @param mixed $url
     * @param mixed $replace
     * @param mixed $flags
     * @param array $newUrl
     * @return string
     */
    function http_build_url($url, $replace = [], $flags = HTTP_URL_REPLACE, array &$newUrl = [])
    {
        return \October\Rain\Router\UrlGenerator::buildUrl($url, $replace, $flags, $newUrl);
    }
}
