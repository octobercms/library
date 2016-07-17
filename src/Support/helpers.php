<?php

if (!function_exists('input'))
{
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
        if ($name === null)
            return Input::all();

        /*
         * Array field name, eg: field[key][key2][key3]
         */
        if (class_exists('October\Rain\Html\Helper')) {
            $name = implode('.', October\Rain\Html\Helper::nameToArray($name));
        }

        return Input::get($name, $default);
    }
}

if (!function_exists('post'))
{
    /**
     * Identical function to input(), however restricted to $_POST values.
     */
    function post($name = null, $default = null)
    {
        if ($name === null)
            return $_POST;

        /*
         * Array field name, eg: field[key][key2][key3]
         */
        if (class_exists('October\Rain\Html\Helper')) {
            $name = implode('.', October\Rain\Html\Helper::nameToArray($name));
        }

        return array_get($_POST, $name, $default);
    }
}


if (!function_exists('get'))
{
    /**
     * Identical function to input(), however restricted to $_GET values.
     */
    function get($name = null, $default = null)
    {
        if ($name === null)
            return $_GET;

        /*
         * Array field name, eg: field[key][key2][key3]
         */
        if (class_exists('October\Rain\Html\Helper')) {
            $name = implode('.', October\Rain\Html\Helper::nameToArray($name));
        }

        return array_get($_GET, $name, $default);
    }
}

if (!function_exists('trace_log'))
{
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

if (!function_exists('traceLog'))
{
    /**
     * Alias for trace_log()
     * @return void
     */
    function traceLog()
    {
        call_user_func_array('trace_log', func_get_args());
    }
}

if (!function_exists('trace_sql'))
{
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

        Event::listen('illuminate.query', function($query, $bindings, $time, $name) {
            $data = compact('bindings', 'time', 'name');

            foreach ($bindings as $i => $binding){

                if ($binding instanceof \DateTime)
                    $bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');

                else if (is_string($binding))
                    $bindings[$i] = "'$binding'";
            }

            $query = str_replace(['%', '?'], ['%%', '%s'], $query);
            $query = vsprintf($query, $bindings);

            traceLog($query);
        });
    }
}

if (!function_exists('traceSql'))
{
    /**
     * Alias for trace_sql()
     * @return void
     */
    function traceSql()
    {
        trace_sql();
    }
}

if (!function_exists('plugins_path'))
{
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

if (!function_exists('uploads_path'))
{
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

if (!function_exists('themes_path'))
{
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

if (!function_exists('temp_path'))
{
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

if (!function_exists('trans'))
{
    /**
     * Translate the given message.
     *
     * @param  string  $id
     * @param  array   $parameters
     * @param  string  $domain
     * @param  string  $locale
     * @return string
     */
    function trans($id = null, $parameters = [], $domain = 'messages', $locale = null)
    {
        return app('translator')->trans($id, $parameters, $domain, $locale);
    }
}