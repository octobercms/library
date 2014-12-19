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
        $keyParts = October\Rain\Support\Str::evalHtmlArray($name);
        $dottedName = implode('.', $keyParts);
        return Input::get($dottedName, $default);
    }
}

if (!function_exists('post'))
{
    /**
     * Identical function to input(), however restricted to $_POST values.
     */
    function post($name = null, $default = null)
    {
        // Remove this line if year >= 2015 (Laravel 5 upgrade)
        return input($name, $default);

        if ($name === null)
            return $_POST;

        /*
         * Array field name, eg: field[key][key2][key3]
         */
        $keyParts = October\Rain\Support\Str::evalHtmlArray($name);
        $dottedName = implode('.', $keyParts);
        return array_get($_POST, $dottedName, $default);
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
        $keyParts = October\Rain\Support\Str::evalHtmlArray($name);
        $dottedName = implode('.', $keyParts);
        return array_get($_GET, $dottedName, $default);
    }
}

if (!function_exists('traceLog'))
{
    /**
     * Writes a trace message to a log file.
     * @param mixed $message Specifies a message to log. The message can be an object, array or string.
     * @param string $level Specifies a level to use. If this parameter is omitted, the default listener will be used (info).
     * @return void
     */
    function traceLog($message, $level = 'info')
    {
        if ($message instanceof Exception)
            $level = 'error';
        elseif (is_array($message) || is_object($message))
            $message = print_r($message, true);

        Log::$level($message);
    }
}

if (!function_exists('traceSql'))
{
    /**
     * Begins to monitor all SQL output.
     * @return void
     */
    function traceSql()
    {
        Event::listen('illuminate.query', function($query, $bindings, $time, $name)
        {
            $data = compact('bindings', 'time', 'name');

            foreach ($bindings as $i => $binding){

                if ($binding instanceof \DateTime)
                    $bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');

                else if (is_string($binding))
                    $bindings[$i] = "'$binding'";
            }

            $query = str_replace(array('%', '?'), array('%%', '%s'), $query);
            $query = vsprintf($query, $bindings);

            traceLog($query);
        });
    }
}
