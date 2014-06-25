<?php namespace October\Rain\Syntax;

/**
 * Dynamic Syntax parser
 */
class TextParser
{
    /**
     * @var array Parsing options
     */
    protected $options = [
        'encodeHtml' => true
    ];

    public function __construct($options = [])
    {
        $this->options = $options;
    }

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
        if (!is_string($string) || !strlen(trim($string)))
            return false;

        foreach ($data as $key => $value) {
            if (is_array($value))
                $string = $this->parseLoop($key, $value, $string);
            else
                $string = $this->parseKey($key, $value, $string);
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
        if (isset($this->options['encodeHtml']) && $this->options['encodeHtml'])
            $value = Html::encode($value);

        $returnStr = str_replace(Parser::CHAR_OPEN.$key.Parser::CHAR_CLOSE, $value, $string);

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

        if (!$match)
            return $string;

        foreach ($data as $row) {
            $matchedText = $match[1];

            foreach ($row as $key => $value) {
                if (is_array($value))
                    $matchedText = $this->parseLoop($key, $value, $matchedText);
                else
                    $matchedText = $this->parseKey($key, $value, $matchedText);
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
    private function parseLoopRegex($string, $key)
    {
        $open = preg_quote(Parser::CHAR_OPEN);
        $close = preg_quote(Parser::CHAR_CLOSE);

        $regex = '|';
        $regex .= $open.$key.$close; // Open
        $regex .= '(.+?)'; // Content
        $regex .= $open.'/'.$key.$close; // Close
        $regex .='|s';

        preg_match($regex, $string, $match);
        return ($match) ? $match : false;
    }

}
