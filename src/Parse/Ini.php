<?php namespace October\Rain\Parse;

/**
 * Initialization (INI) configuration parser that uses "October flavoured INI",
 * with the following improvements:
 *
 * - Parsing supports infinite array nesting
 * - Ability to render INI from a PHP array
 *
 * @package october\parse
 * @author Alexey Bobkov, Samuel Georges
 */
class Ini
{
    /**
     * Parses supplied INI contents in to a PHP array.
     * @param string $contents INI contents to parse.
     * @return array
     */
    public function parse($contents)
    {
        $contents = $this->parsePreProcess($contents);
        $contents = parse_ini_string($contents, true);
        $contents = $this->parsePostProcess($contents);
        return $contents;
    }

    /**
     * Parses supplied INI file contents in to a PHP array.
     * @param $fileName File to read contents and parse.
     * @return array
     */
    public function parseFile($fileName)
    {
        $contents = file_get_contents($fileName);
        return $this->parse($contents);
    }

    /**
     * This method converts key names traditionally invalid, "][", and
     * replaces them with a valid character "|" so parse_ini_string
     * can function correctly. It also forces arrays to have unique
     * indexes so their integrity is maintained.
     * @param string $contents INI contents to parse.
     * @return string
     */
    protected function parsePreProcess($contents)
    {
        $contents = preg_replace('~\R~u', PHP_EOL, $contents); // Normalize EOL
        $contents = explode(PHP_EOL, $contents);
        $count = 0;
        $lastName = null;

        foreach ($contents as $key => $content) {
            if (strpos($content, '=') === false) {
                continue;
            }

            $parts = explode('=', $content, 2);
            if (count($parts) < 2) {
                continue;
            }

            $varName = $parts[0];
            if ($lastName != $varName) {
                $count = 0;
                $lastName = null;
            }

            if (
                ($lastName === null || $lastName == $varName) &&
                strpos($varName, '[]') !== false
            ) {
                $varName = str_replace('[]', '['.$count.']', $varName);
                $count++;
            }

            $lastName = $parts[0];
            $parts[0] = str_replace('][', '|', $varName);
            $contents[$key] = implode('=', $parts);
        }

        return implode(PHP_EOL, $contents);
    }

    /**
     * This method takes the valid key name from pre processing and
     * converts it back to a real PHP array. Eg:
     * - name[validation|regex|message]
     * Converts to:
     * - name => [validation => [regex => [message]]]
     * @param array $array
     * @return array
     */
    protected function parsePostProcess($array)
    {
        $result = [];

        foreach ($array as $key => $value) {
            $this->expandProperty($result, $key, $value);

            if (is_array($value)) {
                $result[$key] = $this->parsePostProcess($value);
            }
        }

        return $result;
    }

    /**
     * Expands a single array property from traditional INI syntax.
     * If no key is given to the method, the entire array will be replaced.
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $value
     * @return array
     */
    public function expandProperty(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('|', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array =& $array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Formats an INI file string from an array
     * @param array $vars Data to format.
     * @param int $level Specifies the level of array value.
     * @return string Returns the INI file string.
     */
    public function render($vars = [], $level = 1)
    {
        $content = '';
        $sections = [];

        foreach ($vars as $key => $value) {
            if (is_array($value)) {
                if ($this->isFinalArray($value)) {
                    foreach ($value as $_value) {
                        $content .= $key.'[] = '.$this->evalValue($_value).PHP_EOL;
                    }
                }
                else {
                    $sections[$key] = $this->renderProperties($value);
                }
            }
            elseif (strlen($value)) {
                $content .= $key.' = '.$this->evalValue($value).PHP_EOL;
            }
        }

        foreach ($sections as $key => $section) {
            $content .= PHP_EOL.'['.$key.']'.PHP_EOL.$section;
        }

        return trim($content);
    }

    /**
     * Renders section properties.
     * @param array $vars
     * @return string
     */
    protected function renderProperties($vars = [])
    {
        $content = '';

        foreach ($vars as $key => $value) {
            if (is_array($value)) {
                if ($this->isFinalArray($value)) {
                    foreach ($value as $_value) {
                        $content .= $key.'[] = '.$this->evalValue($_value).PHP_EOL;
                    }
                }
                else {
                    $value = $this->flattenProperties($value);
                    foreach ($value as $_key => $_value) {
                        if (is_array($_value)) {
                            foreach ($_value as $__value) {
                                $content .= $key.'['.$_key.'][] = '.$this->evalValue($__value).PHP_EOL;
                            }
                        }
                        else {
                            $content .= $key.'['.$_key.'] = '.$this->evalValue($_value).PHP_EOL;
                        }
                    }
                }
            }
            elseif (strlen($value)) {
                $content .= $key.' = '.$this->evalValue($value).PHP_EOL;
            }
        }

        return $content;
    }

    /**
     * Flatten a multi-dimensional associative array for traditional INI syntax.
     * @param  array   $array
     * @param  string  $prepend
     * @return array
     */
    protected function flattenProperties($array, $prepend = '')
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if ($this->isFinalArray($value)) {
                    $results[$prepend.$key] = $value;
                }
                else {
                    $results = array_merge($results, $this->flattenProperties($value, $prepend.$key.']['));
                }
            }
            else {
                $results[$prepend.$key] = $value;
            }
        }

        return $results;
    }

    /**
     * Converts a PHP value to make it suitable for INI format.
     * Strings are escaped.
     * @param string $value Specifies the value to process
     * @return string Returns the processed value
     */
    protected function evalValue($value)
    {
        // Numeric
        if (is_numeric($value)) {
            return $value;
        }

        // String (default)
        return '"'.str_replace('"', '\"', $value).'"';
    }

    /**
     * Checks if the array is the final node in a multidimensional array.
     * Checked supplied array is not associative and contains no array values.
     * @param array $array
     * @return bool
     */
    protected function isFinalArray(array $array)
    {
        return !empty($array) &&
            !count(array_filter($array, 'is_array')) &&
            !count(array_filter(array_keys($array), 'is_string'));
    }
}
