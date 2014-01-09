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

        /*
         * First part will be the field name, pop it off
         */
        $fieldName = array_shift($keyParts);
        if (!Input::has($fieldName))
            return $default;

        $result = Input::get($fieldName);

        /*
         * Loop the remaining key parts and build a result
         */
        foreach ($keyParts as $key) {
            if (!array_key_exists($key, $result))
                return $default;

            $result = $result[$key];
        }

        if (is_string($result))
            $result = October\Rain\Support\Str::evalBoolean(trim($result));

        return $result;
    }
}
