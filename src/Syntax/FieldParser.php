<?php namespace October\Rain\Syntax;

/**
 * Dynamic Syntax parser
 */
class FieldParser
{

    /**
     * @var array Parsing options
     */
    protected $options = [
        'encodeHtml' => true
    ];

    /**
     * @var string Template contents
     */
    protected $template = '';

    /**
     * @var array Extracted fields from the template
     */
    protected $templateFields = [];

    protected $expectedFields = [
        'text',
        'textarea'
    ];

    public function __construct($template)
    {
        $this->template = $template;

        $this->processFields($template);
    }

    public static function parse($template)
    {
        return new static($template);
    }

    protected function processFields($template)
    {
        foreach ($this->expectedFields as $field) {
            $this->processFieldsRegex($template, $field);
        }
    }

    public function processFieldsRegex($string, $key)
    {
        $open = preg_quote(Parser::CHAR_OPEN);
        $close = preg_quote(Parser::CHAR_CLOSE);

        /*
         * Match the opening tag:
         * 
         * {text something="value"}
         * {text\s([^}]+)}
         */
        $regexOpen = $open.$key.'\s'; // Open
        $regexOpen .= '([^'.$close.']+)'; // All but Close tag
        $regexOpen .= $close; // Close

        /*
         * Match all that does not contain another opening tag:
         *
         * (((?!{text)[\s\S])*)
         */
        $regexContent = '('; // Capture
        $regexContent .= '(?:'; // Non capture (negative lookahead)
        $regexContent .= '(?!'.$open.$key.')'; // Not Close tag
        $regexContent .= '[\s\S]'; // All multiline
        $regexContent .= ')'; // End non capture
        $regexContent .= '*)'; // Capture content

        /*
         * Match the closing tag:
         * 
         * {/text}
         */
        $regexClose = $open.'/'.$key.$close; // Close

        $regex = '|';
        $regex .= $regexOpen;
        $regex .='|';

        preg_match_all($regex, $string, $matchSingle);

        $regex = '|';
        $regex .= $regexOpen;
        $regex .= $regexContent;
        $regex .= $regexClose;
        $regex .='|';

        preg_match_all($regex, $string, $matchDouble);

        $match = $matchSingle + $matchDouble;

        echo PHP_EOL.PHP_EOL;
        echo $string;
        echo PHP_EOL.PHP_EOL;
        echo $regex . PHP_EOL;
        echo PHP_EOL.PHP_EOL;
        print_r($match);
        echo PHP_EOL.PHP_EOL;

        return ($match) ? $match : false;
    }
}
