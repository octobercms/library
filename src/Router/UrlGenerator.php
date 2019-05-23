<?php
namespace October\Rain\Router;

use Illuminate\Routing\UrlGenerator as UrlGeneratorBase;

class UrlGenerator extends UrlGeneratorBase
{
    /**
     * Build a URL from an array returned from a `parse_url` call.
     *
     * This function serves as a counterpart to the `parse_url` method available in PHP, and a userland implementation
     * of the `http_build_query` method provided by the PECL HTTP module. This allows a developer to parse a URL to an
     * array and make adjustments to the URL parts before combining them into a valid URL reference string.
     *
     * Based off of the implentation at https://github.com/jakeasmith/http_build_url/blob/master/src/http_build_url.php.
     *
     * @param array $url The URL parts, as an array. Must match the structure returned from a `parse_url` call.
     * @param array $replace The URL replacement parts. Allows a developer to replace certain sections of the URL with
     *                       a different value.
     * @param mixed $flags A bitmask of binary or'ed HTTP_URL constants. By default, this is set to HTTP_URL_REPLACE.
     * @param array $newUrl If set, this will be filled with the array parts of the composed URL, similar to the return
     *                      value of `parse_url`.
     *
     * @return string The generated URL as a string
     */
    public static function buildUrl(array $url, array $replace = [], $flags = HTTP_URL_REPLACE, &$newUrl = []): string
    {
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
