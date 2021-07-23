<?php

/**
 * URL constants as defined in the PHP Manual under "Constants usable with
 * http_build_url()".
 *
 * @see http://us2.php.net/manual/en/http.constants.php#http.constants.url
 */
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
     * Build a URL.
     *
     * The parts of the second URL will be merged into the first according to
     * the flags argument.
     *
     * @see https://github.com/jakeasmith/http_build_url
     *
     * @param mixed $url     (part(s) of) an URL in form of a string or
     *                       associative array like parse_url() returns
     * @param mixed $parts   same as the first argument
     * @param int   $flags   a bitmask of binary or'ed HTTP_URL constants;
     *                       HTTP_URL_REPLACE is the default
     * @param array $new_url if set, it will be filled with the parts of the
     *                       composed url like parse_url() would return
     * @author Jake A. Smith
     * @return string
     */
    function http_build_url($url, $replace = [], $flags = HTTP_URL_REPLACE, &$newUrl = []): string
    {
        if (is_string($url)) {
            $url = parse_url($url);
        }
        if (is_string($replace)) {
            $replace = parse_url($replace);
        }

        $urlSegments = ['scheme', 'host', 'user', 'pass', 'port', 'path', 'query', 'fragment'];

        // Set flags - HTTP_URL_STRIP_ALL and HTTP_URL_STRIP_AUTH cover several other flags.
        if ($flags & HTTP_URL_STRIP_ALL) {
            $flags |= HTTP_URL_STRIP_USER
                   | HTTP_URL_STRIP_PASS
                   | HTTP_URL_STRIP_PORT
                   | HTTP_URL_STRIP_PATH
                   | HTTP_URL_STRIP_QUERY
                   | HTTP_URL_STRIP_FRAGMENT;
        } elseif ($flags & HTTP_URL_STRIP_AUTH) {
            $flags |= HTTP_URL_STRIP_USER
                   | HTTP_URL_STRIP_PASS;
        }

        // Filter $url and $replace arrays to strip out unknown segments
        array_change_key_case($url, CASE_LOWER);
        array_change_key_case($replace, CASE_LOWER);

        $url = array_filter($url, function ($value, $key) use ($urlSegments) {
            return (in_array($key, $urlSegments) && isset($value));
        }, ARRAY_FILTER_USE_BOTH);
        $replace = array_filter($replace, function ($value, $key) use ($urlSegments) {
            return (in_array($key, $urlSegments) && isset($value));
        }, ARRAY_FILTER_USE_BOTH);

        // Replace URL parts if required
        if ($flags & HTTP_URL_REPLACE) {
            $url = array_replace($url, $replace);
        } else {
            // Process joined paths
            if (($flags & HTTP_URL_JOIN_PATH) && isset($replace['path'])) {
                $urlPath = (isset($url['path'])) ? explode('/', trim($url['path'], '/')) : [];
                $joinedPath = explode('/', trim($replace['path'], '/'));

                $url['path'] = '/' . implode('/', array_merge($urlPath, $joinedPath));
            }

            // Process joined query string
            if (($flags & HTTP_URL_JOIN_QUERY) && isset($replace['query'])) {
                $urlQuery = $joinedQuery = [];

                parse_str($url['query'] ?? '', $urlQuery);
                parse_str($replace['query'] ?? '', $joinedQuery);

                $url['query'] = http_build_query(array_replace_recursive($urlQuery, $joinedQuery));
            }
        }

        // Strip segments as necessary
        foreach ($urlSegments as $segment) {
            $strip = 'HTTP_URL_STRIP_' . strtoupper($segment);

            if (!defined($strip)) {
                continue;
            }

            if ($flags & constant($strip)) {
                unset($url[$segment]);
            }
        }

        // Make new URL available
        $newUrl = $url;

        // Generate URL string
        $urlString = '';

        if (!empty($url['scheme'])) {
            $urlString .= $url['scheme'] . '://';
        }
        if (!empty($url['user'])) {
            $urlString .= $url['user'];

            if (!empty($url['pass'])) {
                $urlString .= ':' . $url['pass'];
            }

            $urlString .= '@';
        }
        if (!empty($url['host'])) {
            $urlString .= $url['host'];
        }
        if (!empty($url['port'])) {
            $urlString .= ':' . $url['port'];
        }
        if (!empty($url['path'])) {
            $urlString .= ((substr($url['path'], 0, 1) !== '/') ? '/' : '') . $url['path'];
        }
        if (!empty($url['query'])) {
            $urlString .= '?' . $url['query'];
        }
        if (!empty($url['fragment'])) {
            $urlString .= '#' . $url['fragment'];
        }

        return $urlString;
    }
}

/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Jake A. Smith
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
