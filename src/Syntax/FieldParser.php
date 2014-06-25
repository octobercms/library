<?php namespace October\Rain\Syntax;

/**
 * Dynamic Syntax parser
 */
class FieldParser
{

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

        $match = $this->mergeSinglesAndDoubles($matchSingle, $matchDouble);

        return ($match) ? $match : false;
    }

    private function mergeSinglesAndDoubles($singles, $doubles)
    {
        if (!count($singles[0])) {
            $singles[2] = [];
            return $singles;
        }

        $singles[2] = array_fill(0, count($singles[0]), null);
        $matched = [];
        $result = [];
        foreach ($singles[1] as $singleKey => $needle) {

            $doubleKey = array_search($needle, $doubles[1]);
            if ($doubleKey === false)
                continue;

            $singles[0][$singleKey] = $doubles[0][$doubleKey];
            $singles[2][$singleKey] = $doubles[2][$doubleKey];
        }

        return $singles;
    }

}
