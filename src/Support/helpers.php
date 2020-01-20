<?php

if (!function_exists('input')) {
    /**
     * Returns an input parameter or the default value.
     * Supports HTML Array names.
     * <pre>
     * $value = input('value', 'not found');
     * $name = input('contact[name]');
     * $name = input('contact[location][city]');
     * </pre>
     * Booleans are converted from strings
     * @param string $name
     * @param string $default
     * @return string
     */
    function input($name = null, $default = null)
    {
        if ($name === null) {
            return Request::all();
        }

        /*
         * Array field name, eg: field[key][key2][key3]
         */
        if (class_exists('October\Rain\Html\Helper')) {
            $name = implode('.', October\Rain\Html\Helper::nameToArray($name));
        }

        return Request::input($name, $default);
    }
}

if (!function_exists('post')) {
    /**
     * Identical function to input(), however restricted to POST values.
     */
    function post($name = null, $default = null)
    {
        if ($name === null) {
            return Request::post();
        }

        /*
         * Array field name, eg: field[key][key2][key3]
         */
        if (class_exists('October\Rain\Html\Helper')) {
            $name = implode('.', October\Rain\Html\Helper::nameToArray($name));
        }

        return array_get(Request::post(), $name, $default);
    }
}

if (!function_exists('get')) {
    /**
     * Identical function to input(), however restricted to GET values.
     */
    function get($name = null, $default = null)
    {
        if ($name === null) {
            return Request::query();
        }

        /*
         * Array field name, eg: field[key][key2][key3]
         */
        if (class_exists('October\Rain\Html\Helper')) {
            $name = implode('.', October\Rain\Html\Helper::nameToArray($name));
        }

        return array_get(Request::query(), $name, $default);
    }
}

if (!function_exists('trace_log')) {
    /**
     * Writes a trace message to a log file.
     * @param mixed $message Specifies a message to log. The message can be an object, array or string.
     * @param string $level Specifies a level to use. If this parameter is omitted, the default listener will be used (info).
     * @return void
     */
    function trace_log()
    {
        $messages = func_get_args();

        foreach ($messages as $message) {
            $level = 'info';

            if ($message instanceof Exception) {
                $level = 'error';
            }
            elseif (is_array($message) || is_object($message)) {
                $message = print_r($message, true);
            }

            Log::$level($message);
        }
    }
}

if (!function_exists('traceLog')) {
    /**
     * Alias for trace_log()
     * @return void
     */
    function traceLog()
    {
        call_user_func_array('trace_log', func_get_args());
    }
}

if (!function_exists('trace_sql')) {
    /**
     * Begins to monitor all SQL output.
     * @return void
     */
    function trace_sql()
    {
        if (!defined('OCTOBER_NO_EVENT_LOGGING')) {
            define('OCTOBER_NO_EVENT_LOGGING', 1);
        }

        if (!defined('OCTOBER_TRACING_SQL')) {
            define('OCTOBER_TRACING_SQL', 1);
        }
        else {
            return;
        }

        Event::listen('illuminate.query', function ($query, $bindings, $time, $name) {
            $data = compact('bindings', 'time', 'name');

            foreach ($bindings as $i => $binding) {
                if ($binding instanceof \DateTime) {
                    $bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
                } elseif (is_string($binding)) {
                    $bindings[$i] = "'$binding'";
                }
            }

            $query = str_replace(['%', '?'], ['%%', '%s'], $query);
            $query = vsprintf($query, $bindings);

            traceLog($query);
        });
    }
}

if (!function_exists('traceSql')) {
    /**
     * Alias for trace_sql()
     * @return void
     */
    function traceSql()
    {
        trace_sql();
    }
}

if (!function_exists('plugins_path')) {
    /**
     * Get the path to the plugins folder.
     *
     * @param  string  $path
     * @return string
     */
    function plugins_path($path = '')
    {
        return app('path.plugins').($path ? '/'.$path : $path);
    }
}

if (!function_exists('uploads_path')) {
    /**
     * Get the path to the uploads folder.
     *
     * @param  string  $path
     * @return string
     */
    function uploads_path($path = '')
    {
        return app('path.uploads').($path ? '/'.$path : $path);
    }
}

if (!function_exists('themes_path')) {
    /**
     * Get the path to the themes folder.
     *
     * @param  string  $path
     * @return string
     */
    function themes_path($path = '')
    {
        return app('path.themes').($path ? '/'.$path : $path);
    }
}

if (!function_exists('temp_path')) {
    /**
     * Get the path to the temporary storage folder.
     *
     * @param  string  $path
     * @return string
     */
    function temp_path($path = '')
    {
        return app('path.temp').($path ? '/'.$path : $path);
    }
}

if (!function_exists('e')) {
    /**
     * Encode HTML special characters in a string.
     *
     * @param  \Illuminate\Contracts\Support\Htmlable|string  $value
     * @param  bool  $doubleEncode
     * @return string
     */
    function e($value, $doubleEncode = false)
    {
        if ($value instanceof \Illuminate\Contracts\Support\Htmlable) {
            return $value->toHtml();
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', $doubleEncode);
    }
}

if (!function_exists('trans')) {
    /**
     * Translate the given message.
     *
     * @param  string|null  $id
     * @param  array   $parameters
     * @param  string|null  $locale
     * @return string
     */
    function trans($id = null, $parameters = [], $locale = null)
    {
        return app('translator')->trans($id, $parameters, $locale);
    }
}

if (!function_exists('__')) {
    /**
     * Translate the given message.
     *
     * @param  string|null  $id
     * @param  array   $parameters
     * @param  string|null  $locale
     * @return string
     */
    function __($id = null, $parameters = [], $locale = null)
    {
        return trans($id, $parameters, $locale);
    }
}

if (!function_exists('collect')) {
    /**
     * Create a collection from the given value.
     *
     * @param  mixed  $value
     * @return \October\Rain\Support\Collection
     */
    function collect($value = null)
    {
        return new \October\Rain\Support\Collection($value);
    }
}

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
     * @param array $url
     * @param array $replace
     * @param mixed $flags
     * @param array $newUrl
     * @return string
     */
    function http_build_url(array $url, array $replace = [], $flags = HTTP_URL_REPLACE, array &$newUrl = []): string
    {
        return \October\Rain\Router\UrlGenerator::buildUrl($url, $replace, $flags, $newUrl);
    }
}

if (!function_exists('is_countable')) {
    /**
     * Polyfill for `is_countable` method provided in PHP 7.3
     *
     * @param  mixed  $var
     * @return boolean
     */
    function is_countable($value)
    {
        return (is_array($value) || $value instanceof Countable);
    }
}

/**
 * Laravel helpers
 */

if (!function_exists('array_add')) {
    /**
     * Add an element to an array using "dot" notation if it doesn't exist.
     *
     * @param  array  $array
     * @param  string  $key
     * @param  mixed  $value
     * @return array
     */
    function array_add($array, $key, $value)
    {
        return \October\Rain\Support\Arr::add($array, $key, $value);
    }
}

if (!function_exists('array_build')) {
    /**
     * Build a new array using a callback.
     *
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     */
    function array_build($array, callable $callback)
    {
        return \October\Rain\Support\Arr::build($array, $callback);
    }
}

if (!function_exists('array_collapse')) {
    /**
     * Collapse an array of arrays into a single array.
     *
     * @param  array  $array
     * @return array
     */
    function array_collapse($array)
    {
        return \October\Rain\Support\Arr::collapse($array);
    }
}

if (!function_exists('array_divide')) {
    /**
     * Divide an array into two arrays. One with keys and the other with values.
     *
     * @param  array  $array
     * @return array
     */
    function array_divide($array)
    {
        return \October\Rain\Support\Arr::divide($array);
    }
}

if (!function_exists('array_dot')) {
    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param  array  $array
     * @param  string  $prepend
     * @return array
     */
    function array_dot($array, $prepend = '')
    {
        return \October\Rain\Support\Arr::dot($array, $prepend);
    }
}

if (!function_exists('array_except')) {
    /**
     * Get all of the given array except for a specified array of keys.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    function array_except($array, $keys)
    {
        return \October\Rain\Support\Arr::except($array, $keys);
    }
}

if (!function_exists('array_first')) {
    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param  array  $array
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    function array_first($array, callable $callback = null, $default = null)
    {
        return \October\Rain\Support\Arr::first($array, $callback, $default);
    }
}

if (!function_exists('array_flatten')) {
    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param  array  $array
     * @param  int  $depth
     * @return array
     */
    function array_flatten($array, $depth = INF)
    {
        return \October\Rain\Support\Arr::flatten($array, $depth);
    }
}

if (!function_exists('array_forget')) {
    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return void
     */
    function array_forget(&$array, $keys)
    {
        \October\Rain\Support\Arr::forget($array, $keys);
    }
}

if (!function_exists('array_get')) {
    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function array_get($array, $key, $default = null)
    {
        return \October\Rain\Support\Arr::get($array, $key, $default);
    }
}

if (!function_exists('array_has')) {
    /**
     * Check if an item or items exist in an array using "dot" notation.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|array  $keys
     * @return bool
     */
    function array_has($array, $keys)
    {
        return \October\Rain\Support\Arr::has($array, $keys);
    }
}

if (!function_exists('array_last')) {
    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param  array  $array
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    function array_last($array, callable $callback = null, $default = null)
    {
        return \October\Rain\Support\Arr::last($array, $callback, $default);
    }
}

if (!function_exists('array_only')) {
    /**
     * Get a subset of the items from the given array.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    function array_only($array, $keys)
    {
        return \October\Rain\Support\Arr::only($array, $keys);
    }
}

if (!function_exists('array_pluck')) {
    /**
     * Pluck an array of values from an array.
     *
     * @param  array  $array
     * @param  string|array  $value
     * @param  string|array|null  $key
     * @return array
     */
    function array_pluck($array, $value, $key = null)
    {
        return \October\Rain\Support\Arr::pluck($array, $value, $key);
    }
}

if (!function_exists('array_prepend')) {
    /**
     * Push an item onto the beginning of an array.
     *
     * @param  array  $array
     * @param  mixed  $value
     * @param  mixed  $key
     * @return array
     */
    function array_prepend($array, $value, $key = null)
    {
        return \October\Rain\Support\Arr::prepend($array, $value, $key);
    }
}

if (!function_exists('array_pull')) {
    /**
     * Get a value from the array, and remove it.
     *
     * @param  array  $array
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function array_pull(&$array, $key, $default = null)
    {
        return \October\Rain\Support\Arr::pull($array, $key, $default);
    }
}

if (!function_exists('array_random')) {
    /**
     * Get a random value from an array.
     *
     * @param  array  $array
     * @param  int|null  $num
     * @return mixed
     */
    function array_random($array, $num = null)
    {
        return \October\Rain\Support\Arr::random($array, $num);
    }
}

if (!function_exists('array_set')) {
    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param  array  $array
     * @param  string  $key
     * @param  mixed  $value
     * @return array
     */
    function array_set(&$array, $key, $value)
    {
        return \October\Rain\Support\Arr::set($array, $key, $value);
    }
}

if (!function_exists('array_sort')) {
    /**
     * Sort the array by the given callback or attribute name.
     *
     * @param  array  $array
     * @param  callable|string|null  $callback
     * @return array
     */
    function array_sort($array, $callback = null)
    {
        return \October\Rain\Support\Arr::sort($array, $callback);
    }
}

if (!function_exists('array_sort_recursive')) {
    /**
     * Recursively sort an array by keys and values.
     *
     * @param  array  $array
     * @return array
     */
    function array_sort_recursive($array)
    {
        return \October\Rain\Support\Arr::sortRecursive($array);
    }
}

if (!function_exists('array_where')) {
    /**
     * Filter the array using the given callback.
     *
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     */
    function array_where($array, callable $callback)
    {
        return \October\Rain\Support\Arr::where($array, $callback);
    }
}

if (!function_exists('array_wrap')) {
    /**
     * If the given value is not an array, wrap it in one.
     *
     * @param  mixed  $value
     * @return array
     */
    function array_wrap($value)
    {
        return \October\Rain\Support\Arr::wrap($value);
    }
}

if (!function_exists('camel_case')) {
    /**
     * Convert a value to camel case.
     *
     * @param  string  $value
     * @return string
     */
    function camel_case($value)
    {
        return \October\Rain\Support\Str::camel($value);
    }
}

if (!function_exists('ends_with')) {
    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function ends_with($haystack, $needles)
    {
        return \October\Rain\Support\Str::endsWith($haystack, $needles);
    }
}

if (!function_exists('kebab_case')) {
    /**
     * Convert a string to kebab case.
     *
     * @param  string  $value
     * @return string
     */
    function kebab_case($value)
    {
        return \October\Rain\Support\Str::kebab($value);
    }
}

if (!function_exists('snake_case')) {
    /**
     * Convert a string to snake case.
     *
     * @param  string  $value
     * @param  string  $delimiter
     * @return string
     */
    function snake_case($value, $delimiter = '_')
    {
        return \October\Rain\Support\Str::snake($value, $delimiter);
    }
}

if (!function_exists('starts_with')) {
    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function starts_with($haystack, $needles)
    {
        return \October\Rain\Support\Str::startsWith($haystack, $needles);
    }
}

if (!function_exists('str_after')) {
    /**
     * Return the remainder of a string after a given value.
     *
     * @param  string  $subject
     * @param  string  $search
     * @return string
     */
    function str_after($subject, $search)
    {
        return \October\Rain\Support\Str::after($subject, $search);
    }
}

if (!function_exists('str_before')) {
    /**
     * Get the portion of a string before a given value.
     *
     * @param  string  $subject
     * @param  string  $search
     * @return string
     */
    function str_before($subject, $search)
    {
        return \October\Rain\Support\Str::before($subject, $search);
    }
}

if (!function_exists('str_contains')) {
    /**
     * Determine if a given string contains a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function str_contains($haystack, $needles)
    {
        return \October\Rain\Support\Str::contains($haystack, $needles);
    }
}

if (!function_exists('str_finish')) {
    /**
     * Cap a string with a single instance of a given value.
     *
     * @param  string  $value
     * @param  string  $cap
     * @return string
     */
    function str_finish($value, $cap)
    {
        return \October\Rain\Support\Str::finish($value, $cap);
    }
}

if (!function_exists('str_is')) {
    /**
     * Determine if a given string matches a given pattern.
     *
     * @param  string|array  $pattern
     * @param  string  $value
     * @return bool
     */
    function str_is($pattern, $value)
    {
        return \October\Rain\Support\Str::is($pattern, $value);
    }
}

if (!function_exists('str_limit')) {
    /**
     * Limit the number of characters in a string.
     *
     * @param  string  $value
     * @param  int  $limit
     * @param  string  $end
     * @return string
     */
    function str_limit($value, $limit = 100, $end = '...')
    {
        return \October\Rain\Support\Str::limit($value, $limit, $end);
    }
}

if (!function_exists('str_plural')) {
    /**
     * Get the plural form of an English word.
     *
     * @param  string  $value
     * @param  int  $count
     * @return string
     */
    function str_plural($value, $count = 2)
    {
        return \October\Rain\Support\Str::plural($value, $count);
    }
}

if (!function_exists('str_random')) {
    /**
     * Generate a more truly "random" alpha-numeric string.
     *
     * @param  int  $length
     * @return string
     *
     * @throws \RuntimeException
     */
    function str_random($length = 16)
    {
        return \October\Rain\Support\Str::random($length);
    }
}

if (!function_exists('str_replace_array')) {
    /**
     * Replace a given value in the string sequentially with an array.
     *
     * @param  string  $search
     * @param  array  $replace
     * @param  string  $subject
     * @return string
     */
    function str_replace_array($search, array $replace, $subject)
    {
        return \October\Rain\Support\Str::replaceArray($search, $replace, $subject);
    }
}

if (!function_exists('str_replace_first')) {
    /**
     * Replace the first occurrence of a given value in the string.
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $subject
     * @return string
     */
    function str_replace_first($search, $replace, $subject)
    {
        return \October\Rain\Support\Str::replaceFirst($search, $replace, $subject);
    }
}

if (!function_exists('str_replace_last')) {
    /**
     * Replace the last occurrence of a given value in the string.
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $subject
     * @return string
     */
    function str_replace_last($search, $replace, $subject)
    {
        return \October\Rain\Support\Str::replaceLast($search, $replace, $subject);
    }
}

if (!function_exists('str_singular')) {
    /**
     * Get the singular form of an English word.
     *
     * @param  string  $value
     * @return string
     */
    function str_singular($value)
    {
        return \October\Rain\Support\Str::singular($value);
    }
}

if (!function_exists('str_slug')) {
    /**
     * Generate a URL friendly "slug" from a given string.
     *
     * @param  string  $title
     * @param  string  $separator
     * @param  string  $language
     * @return string
     */
    function str_slug($title, $separator = '-', $language = 'en')
    {
        return \October\Rain\Support\Str::slug($title, $separator, $language);
    }
}

if (!function_exists('str_start')) {
    /**
     * Begin a string with a single instance of a given value.
     *
     * @param  string  $value
     * @param  string  $prefix
     * @return string
     */
    function str_start($value, $prefix)
    {
        return \October\Rain\Support\Str::start($value, $prefix);
    }
}

if (!function_exists('studly_case')) {
    /**
     * Convert a value to studly caps case.
     *
     * @param  string  $value
     * @return string
     */
    function studly_case($value)
    {
        return \October\Rain\Support\Str::studly($value);
    }
}

if (!function_exists('title_case')) {
    /**
     * Convert a value to title case.
     *
     * @param  string  $value
     * @return string
     */
    function title_case($value)
    {
        return \October\Rain\Support\Str::title($value);
    }
}
