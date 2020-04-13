<?php namespace October\Rain\Parse;

/**
 * Bracket parser
 *
 * @package october\parse
 * @author Alexey Bobkov, Samuel Georges
 */
class Bracket
{
    const CHAR_OPEN = '{';
    const CHAR_CLOSE = '}';

    /**
     * @var array Parsing options
     */
    protected $options = [
        'encodeHtml' => false,
        'newlineToBr' => false,
        'filters' => []
    ];

    public function __construct($options = [])
    {
        $this->setOptions($options);
    }

    public function setOptions($options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Static helper for new instances of this class.
     * @param  string $template
     * @param  array $vars
     * @param  array $options
     * @return self
     */
    public static function parse($template, $vars = [], $options = [])
    {
        $obj = new static($options);
        return $obj->parseString($template, $vars);
    }

    /**
     * Parse a string against data
     * @param  string $string
     * @param  array $data
     * @return string
     */
    public function parseString($string, $data)
    {
        if (!is_string($string) || !strlen(trim($string))) {
            return false;
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $string = $this->parseLoop($key, $value, $string);
            }
            else {
                $string = $this->parseKey($key, $value, $string);
                $string = $this->parseKeyFilters($key, $value, $string);
                $string = $this->parseKeyBooleans($key, $value, $string);
            }
        }

        return $string;
    }

    /**
     * Process a single key
     * @param  string $key
     * @param  string $value
     * @param  string $string
     * @return string
     */
    protected function parseKey($key, $value, $string)
    {
        if (isset($this->options['encodeHtml']) && $this->options['encodeHtml']) {
            $value = htmlentities($value, ENT_QUOTES, 'UTF-8', false);
        }

        if (isset($this->options['newlineToBr']) && $this->options['newlineToBr']) {
            $value = nl2br($value);
        }

        $returnStr = str_replace(static::CHAR_OPEN.$key.static::CHAR_CLOSE, $value, $string);

        return $returnStr;
    }

    /**
     * Look for filtered variables and replace them
     * @param  string $key
     * @param  string $value
     * @param  string $string
     * @return string
     */
    protected function parseKeyFilters($key, $value, $string)
    {
        if (!$filters = $this->options['filters']) {
            return $string;
        }

        $returnStr = $string;

        foreach ($filters as $filter => $func) {
            $charKey = static::CHAR_OPEN.$key.'|'.$filter.static::CHAR_CLOSE;

            if (is_callable($func) && strpos($string, $charKey) !== false) {
                $returnStr = str_replace($charKey, $func($value), $returnStr);
            }
        }

        return $returnStr;
    }

    /**
     * This is an internally used method, the syntax is experimental and may change.
     */
    protected function parseKeyBooleans($key, $value, $string)
    {
        $openKey = static::CHAR_OPEN.'?'.$key.static::CHAR_CLOSE;
        $closeKey = static::CHAR_OPEN.'/'.$key.static::CHAR_CLOSE;

        if ($value) {
            $returnStr = str_replace([$openKey, $closeKey], '', $string);
        }
        else {
            $open = preg_quote($openKey);
            $close = preg_quote($closeKey);
            $returnStr = preg_replace('|'.$open.'[\s\S]+?'.$close.'|s', '', $string);
        }

        return $returnStr;
    }

    /**
     * Search for open/close keys and process them in a nested fashion
     * @param  string $key
     * @param  array  $data
     * @param  string $string
     * @return string
     */
    protected function parseLoop($key, $data, $string)
    {
        $returnStr = '';
        $match = $this->parseLoopRegex($string, $key);

        if (!$match) {
            return $string;
        }

        foreach ($data as $row) {
            $matchedText = $match[1];

            foreach ($row as $key => $value) {
                if (is_array($value)) {
                    $matchedText = $this->parseLoop($key, $value, $matchedText);
                }
                else {
                    $matchedText = $this->parseKey($key, $value, $matchedText);
                    $matchedText = $this->parseKeyFilters($key, $value, $matchedText);
                    $matchedText = $this->parseKeyBooleans($key, $value, $matchedText);
                }
            }

            $returnStr .= $matchedText;
        }

        return str_replace($match[0], $returnStr, $string);
    }

    /**
     * Internal method, returns a Regular expression for parsing
     * a looping tag.
     * @param  string $string
     * @param  string $key
     * @return string
     */
    protected function parseLoopRegex($string, $key)
    {
        $open = preg_quote(static::CHAR_OPEN);
        $close = preg_quote(static::CHAR_CLOSE);

        $regex = '|';
        $regex .= $open.$key.$close; // Open
        $regex .= '(.+?)'; // Content
        $regex .= $open.'/'.$key.$close; // Close
        $regex .='|s';

        preg_match($regex, $string, $match);
        return $match ?: false;
    }
}
