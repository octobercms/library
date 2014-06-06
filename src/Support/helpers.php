<?php

if (!function_exists('post'))
{
    /**
     * Returns an input parameter or the default value.
     * Supports HTML Array names.
     * <pre>
     * $value = post('value', 'not found');
     * $name = post('contact[name]');
     * $name = post('contact[location][city]');
     * </pre>
     * Booleans are converted from strings
     * @param string $name
     * @param string $default
     * @return string
     */
    function post($name = null, $default = null)
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
        if (is_array($message) || is_object($message))
            $message = print_r($message, true);

        forward_static_call(['Log', $level], $message);
    }
}