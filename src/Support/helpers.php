<?php

use October\Rain\Support\Arr;
use October\Rain\Support\Str;
use October\Rain\Support\Collection;

if (!function_exists('input')) {
    /**
     * input returns an input parameter or the default value.
     * Supports HTML Array names.
     *
     *     $value = input('value', 'not found');
     *     $name = input('contact[name]');
     *     $name = input('contact[location][city]');
     *
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

        return Request::get($name, $default);
    }
}

if (!function_exists('post')) {
    /**
     * post is an identical function to input(), however restricted to POST methods.
     */
    function post($name = null, $default = null)
    {
        if (Request::getRealMethod() !== 'POST') {
            return $default;
        }

        if ($name === null) {
            return Request::all();
        }

        /*
         * Array field name, eg: field[key][key2][key3]
         */
        if (class_exists('October\Rain\Html\Helper')) {
            $name = implode('.', October\Rain\Html\Helper::nameToArray($name));
        }

        return array_get(Request::all(), $name, $default);
    }
}

if (!function_exists('get')) {
    /**
     * get is an identical function to input(), however restricted to GET values.
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
     * trace_log writes a trace message to a log file
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
     * traceLog is an alias for trace_log()
     */
    function traceLog()
    {
        call_user_func_array('trace_log', func_get_args());
    }
}

if (!function_exists('trace_sql')) {
    /**
     * trace_sql begins to monitor all SQL output
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
                }
                elseif (is_string($binding)) {
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
     * traceSql is an alias for trace_sql()
     * @return void
     */
    function traceSql()
    {
        trace_sql();
    }
}

if (!function_exists('plugins_path')) {
    /**
     * plugins_path gets the path to the plugins folder
     * @param  string  $path
     * @return string
     */
    function plugins_path($path = '')
    {
        return app('path.plugins').($path ? '/'.$path : $path);
    }
}

if (!function_exists('cache_path')) {
    /**
     * cache_path gets the path to the cache folder
     * @param  string  $path
     * @return string
     */
    function cache_path($path = '')
    {
        return app('path.cache').($path ? '/'.$path : $path);
    }
}

if (!function_exists('themes_path')) {
    /**
     * themes_path gets the path to the themes folder
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
     * temp_path gets the path to the temporary storage folder
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
     * e will encode HTML special characters in a string
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
     * trans translates the given message
     * @param  string  $id
     * @param  array   $parameters
     * @param  string  $locale
     * @return string
     */
    function trans($id = null, $parameters = [], $locale = null)
    {
        return app('translator')->trans($id, $parameters, $locale);
    }
}

if (!function_exists('array_build')) {
    /**
     * array_build builds a new array using a callback
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     */
    function array_build($array, callable $callback)
    {
        return Arr::build($array, $callback);
    }
}

if (!function_exists('collect')) {
    /**
     * collect creates a collection from the given value
     * @param  mixed  $value
     * @return \October\Rain\Support\Collection
     */
    function collect($value = null)
    {
        return new Collection($value);
    }
}

if (!function_exists('is_countable')) {
    /**
     * is_countable is a polyfill for `is_countable` method provided in PHP 7.3
     * @param  mixed  $var
     * @return boolean
     */
    function is_countable($value)
    {
        return (is_array($value) || $value instanceof Countable);
    }
}

if (!function_exists('array_add')) {
    /**
     * array_add adds an element to an array using "dot" notation if it doesn't exist
     * @param  array  $array
     * @param  string  $key
     * @param  mixed  $value
     * @return array
     */
    function array_add($array, $key, $value)
    {
        return Arr::add($array, $key, $value);
    }
}

if (!function_exists('array_collapse')) {
    /**
     * array_collapse collapses an array of arrays into a single array
     * @param  array  $array
     * @return array
     */
    function array_collapse($array)
    {
        return Arr::collapse($array);
    }
}

if (!function_exists('array_divide')) {
    /**
     * array_divide divides an array into two arrays. One with keys and the other with values
     * @param  array  $array
     * @return array
     */
    function array_divide($array)
    {
        return Arr::divide($array);
    }
}

if (!function_exists('array_dot')) {
    /**
     * array_dot flattens a multi-dimensional associative array with dots
     * @param  array  $array
     * @param  string  $prepend
     * @return array
     */
    function array_dot($array, $prepend = '')
    {
        return Arr::dot($array, $prepend);
    }
}

if (!function_exists('array_except')) {
    /**
     * array_except gets all of the given array except for a specified array of keys
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    function array_except($array, $keys)
    {
        return Arr::except($array, $keys);
    }
}

if (!function_exists('array_first')) {
    /**
     * array_first returns the first element in an array passing a given truth test
     * @param  array  $array
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    function array_first($array, callable $callback = null, $default = null)
    {
        return Arr::first($array, $callback, $default);
    }
}

if (!function_exists('array_flatten')) {
    /**
     * array_flatten flattens a multi-dimensional array into a single level
     * @param  array  $array
     * @param  int  $depth
     * @return array
     */
    function array_flatten($array, $depth = INF)
    {
        return Arr::flatten($array, $depth);
    }
}

if (!function_exists('array_forget')) {
    /**
     * array_forget removes one or many array items from a given array using "dot" notation
     * @param  array  $array
     * @param  array|string  $keys
     * @return void
     */
    function array_forget(&$array, $keys)
    {
        Arr::forget($array, $keys);
    }
}

if (!function_exists('array_get')) {
    /**
     * array_get gets an item from an array using "dot" notation
     * @param  \ArrayAccess|array  $array
     * @param  string|int  $key
     * @param  mixed  $default
     * @return mixed
     */
    function array_get($array, $key, $default = null)
    {
        return Arr::get($array, $key, $default);
    }
}

if (!function_exists('array_has')) {
    /**
     * array_has checks if an item or items exist in an array using "dot" notation
     * @param  \ArrayAccess|array  $array
     * @param  string|array  $keys
     * @return bool
     */
    function array_has($array, $keys)
    {
        return Arr::has($array, $keys);
    }
}

if (!function_exists('array_last')) {
    /**
     * array_last returns the last element in an array passing a given truth test
     * @param  array  $array
     * @param  callable|null  $callback
     * @param  mixed  $default
     * @return mixed
     */
    function array_last($array, callable $callback = null, $default = null)
    {
        return Arr::last($array, $callback, $default);
    }
}

if (!function_exists('array_only')) {
    /**
     * array_only gets a subset of the items from the given array
     * @param  array  $array
     * @param  array|string  $keys
     * @return array
     */
    function array_only($array, $keys)
    {
        return Arr::only($array, $keys);
    }
}

if (!function_exists('array_pluck')) {
    /**
     * array_pluck plucks an array of values from an array
     * @param  array  $array
     * @param  string|array  $value
     * @param  string|array|null  $key
     * @return array
     */
    function array_pluck($array, $value, $key = null)
    {
        return Arr::pluck($array, $value, $key);
    }
}

if (!function_exists('array_prepend')) {
    /**
     * array_prepend pushes an item onto the beginning of an array
     * @param  array  $array
     * @param  mixed  $value
     * @param  mixed  $key
     * @return array
     */
    function array_prepend($array, $value, $key = null)
    {
        return Arr::prepend($array, $value, $key);
    }
}

if (!function_exists('array_pull')) {
    /**
     * array_pull gets a value from the array, and remove it
     * @param  array  $array
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function array_pull(&$array, $key, $default = null)
    {
        return Arr::pull($array, $key, $default);
    }
}

if (!function_exists('array_random')) {
    /**
     * array_random gets a random value from an array
     * @param  array  $array
     * @param  int|null  $num
     * @return mixed
     */
    function array_random($array, $num = null)
    {
        return Arr::random($array, $num);
    }
}

if (!function_exists('array_set')) {
    /**
     * array_set sets an array item to a given value using "dot" notation
     * If no key is given to the method, the entire array will be replaced.
     * @param  array  $array
     * @param  string  $key
     * @param  mixed  $value
     * @return array
     */
    function array_set(&$array, $key, $value)
    {
        return Arr::set($array, $key, $value);
    }
}

if (!function_exists('array_sort')) {
    /**
     * array_sort sorts the array by the given callback or attribute name
     * @param  array  $array
     * @param  callable|string|null  $callback
     * @return array
     */
    function array_sort($array, $callback = null)
    {
        return Arr::sort($array, $callback);
    }
}

if (!function_exists('array_sort_recursive')) {
    /**
     * array_sort_recursive recursively sort an array by keys and values
     * @param  array  $array
     * @return array
     */
    function array_sort_recursive($array)
    {
        return Arr::sortRecursive($array);
    }
}

if (!function_exists('array_where')) {
    /**
     * array_where filters the array using the given callback
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     */
    function array_where($array, callable $callback)
    {
        return Arr::where($array, $callback);
    }
}

if (!function_exists('array_wrap')) {
    /**
     * array_wrap if the given value is not an array, wrap it in one
     * @param  mixed  $value
     * @return array
     */
    function array_wrap($value)
    {
        return Arr::wrap($value);
    }
}

if (!function_exists('camel_case')) {
    /**
     * camel_case converts a value to camel case
     * @param  string  $value
     * @return string
     */
    function camel_case($value)
    {
        return Str::camel($value);
    }
}

if (!function_exists('ends_with')) {
    /**
     * ends_with determines if a given string ends with a given substring
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function ends_with($haystack, $needles)
    {
        return Str::endsWith($haystack, $needles);
    }
}

if (!function_exists('kebab_case')) {
    /**
     * kebab_case converts a string to kebab case
     * @param  string  $value
     * @return string
     */
    function kebab_case($value)
    {
        return Str::kebab($value);
    }
}

if (!function_exists('snake_case')) {
    /**
     * snake_case converts a string to snake case
     * @param  string  $value
     * @param  string  $delimiter
     * @return string
     */
    function snake_case($value, $delimiter = '_')
    {
        return Str::snake($value, $delimiter);
    }
}

if (!function_exists('starts_with')) {
    /**
     * starts_with determines if a given string starts with a given substring
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function starts_with($haystack, $needles)
    {
        return Str::startsWith($haystack, $needles);
    }
}

if (!function_exists('str_after')) {
    /**
     * str_after returns the remainder of a string after a given value
     * @param  string  $subject
     * @param  string  $search
     * @return string
     */
    function str_after($subject, $search)
    {
        return Str::after($subject, $search);
    }
}

if (!function_exists('str_before')) {
    /**
     * str_before get the portion of a string before a given value
     * @param  string  $subject
     * @param  string  $search
     * @return string
     */
    function str_before($subject, $search)
    {
        return Str::before($subject, $search);
    }
}

if (!function_exists('str_contains')) {
    /**
     * str_contains determines if a given string contains a given substring
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function str_contains($haystack, $needles)
    {
        return Str::contains($haystack, $needles);
    }
}

if (!function_exists('str_finish')) {
    /**
     * str_finish caps a string with a single instance of a given value
     * @param  string  $value
     * @param  string  $cap
     * @return string
     */
    function str_finish($value, $cap)
    {
        return Str::finish($value, $cap);
    }
}

if (!function_exists('str_is')) {
    /**
     * str_is determines if a given string matches a given pattern
     * @param  string|array  $pattern
     * @param  string  $value
     * @return bool
     */
    function str_is($pattern, $value)
    {
        return Str::is($pattern, $value);
    }
}

if (!function_exists('str_limit')) {
    /**
     * str_limit limits the number of characters in a string
     * @param  string  $value
     * @param  int  $limit
     * @param  string  $end
     * @return string
     */
    function str_limit($value, $limit = 100, $end = '...')
    {
        return Str::limit($value, $limit, $end);
    }
}

if (!function_exists('str_plural')) {
    /**
     * str_plural get the plural form of an English word
     * @param  string  $value
     * @param  int  $count
     * @return string
     */
    function str_plural($value, $count = 2)
    {
        return Str::plural($value, $count);
    }
}

if (!function_exists('str_random')) {
    /**
     * str_random generates a more truly "random" alpha-numeric string
     * @param  int  $length
     * @return string
     *
     * @throws \RuntimeException
     */
    function str_random($length = 16)
    {
        return Str::random($length);
    }
}

if (!function_exists('str_replace_array')) {
    /**
     * str_replace_array replaces a given value in the string sequentially with an array
     * @param  string  $search
     * @param  array  $replace
     * @param  string  $subject
     * @return string
     */
    function str_replace_array($search, array $replace, $subject)
    {
        return Str::replaceArray($search, $replace, $subject);
    }
}

if (!function_exists('str_replace_first')) {
    /**
     * str_replace_first replaces the first occurrence of a given value in the string
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $subject
     * @return string
     */
    function str_replace_first($search, $replace, $subject)
    {
        return Str::replaceFirst($search, $replace, $subject);
    }
}

if (!function_exists('str_replace_last')) {
    /**
     * str_replace_last replaces the last occurrence of a given value in the string
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $subject
     * @return string
     */
    function str_replace_last($search, $replace, $subject)
    {
        return Str::replaceLast($search, $replace, $subject);
    }
}

if (!function_exists('str_singular')) {
    /**
     * str_singular get the singular form of an English word
     * @param  string  $value
     * @return string
     */
    function str_singular($value)
    {
        return Str::singular($value);
    }
}

if (!function_exists('str_slug')) {
    /**
     * str_slug generates a URL friendly "slug" from a given string
     * @param  string  $title
     * @param  string  $separator
     * @param  string  $language
     * @return string
     */
    function str_slug($title, $separator = '-', $language = 'en')
    {
        return Str::slug($title, $separator, $language);
    }
}

if (!function_exists('str_start')) {
    /**
     * str_start begins a string with a single instance of a given value
     * @param  string  $value
     * @param  string  $prefix
     * @return string
     */
    function str_start($value, $prefix)
    {
        return Str::start($value, $prefix);
    }
}

if (!function_exists('studly_case')) {
    /**
     * studly_case converts a value to studly caps case
     * @param  string  $value
     * @return string
     */
    function studly_case($value)
    {
        return Str::studly($value);
    }
}

if (!function_exists('title_case')) {
    /**
     * title_case converts a value to title case
     * @param  string  $value
     * @return string
     */
    function title_case($value)
    {
        return Str::title($value);
    }
}
