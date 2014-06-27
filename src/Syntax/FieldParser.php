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
     * The array key should match a unique field name, and the value 
     * is another array with values:
     *
     * - type: the tag name, eg: text
     * - default: the default tag text
     * - *: defined parameters
     */
    protected $fields = [];

    /**
     * @var array Complete tag strings for each field. The array
     * key will match the unique field name and the value is the
     * complete tag string, eg: {text}...{/text}
     */
    protected $tags = [];

    /**
     * @var array Registered template tags
     */
    protected $registeredTags = [
        'text',
        'textarea'
    ];

    /**
     * Constructor
     * @param string $template Template to parse.
     */
    public function __construct($template = null)
    {
        if ($template) {
            $this->template = $template;
            $this->processTags($template);
        }
    }

    /**
     * Static helper for new instances of this class.
     * @param  string $template
     * @return self
     */
    public static function parse($template)
    {
        return new static($template);
    }

    /**
     * Returns all field definitions found in the template
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Returns defined parameters for a single field
     * @param  string $field
     * @return array
     */
    public function getFieldParams($field)
    {
        return isset($this->fields[$field])
            ? $this->fields[$field]
            : [];
    }

    /**
     * Returns default values for all fields.
     * @return array
     */
    public function getDefaultParams()
    {
        $defaults = [];
        foreach ($this->fields as $field => $params) {
            $defaults[$field] = isset($params['default']) ? $params['default'] : null;
        }
        return $defaults;
    }

    /**
     * Returns all tag strings found in the template
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Processes all registered tags against a template.
     * @param  string $template
     * @return void
     */
    protected function processTags($template)
    {
        foreach ($this->registeredTags as $tag) {
            list($tags, $fields) = $this->processTag($template, $tag);
            $this->tags = $this->tags + $tags;
            $this->fields = $this->fields + $fields;
        }
    }

    /**
     * Processes the template text for a single tag, returns
     * two arrays: tag strings and field definitions.
     * @param  string $template
     * @param  string $tag
     * @return array
     */
    protected function processTag($template, $tag)
    {
        $tags = [];
        $fields = [];

        $result = $this->processTagsRegex($template, $tag);
        $tagStrings = $result[0];
        $paramStrings = $result[1];
        $defaultValues = $result[2];

        foreach ($tagStrings as $key => $tagString) {
            $result = $this->processParamsRegex($paramStrings[$key]);
            $paramNames = $result[1];
            $paramValues = $result[2];
            $params = array_combine($paramNames, $paramValues);

            if (isset($params['name'])) {
                $name = $params['name'];
                unset($params['name']);
            }
            else {
                $name = md5($tagString);
            }

            $params['type'] = $tag;
            $params['default'] = $defaultValues[$key];

            $tags[$name] = $tagString;
            $fields[$name] = $params;
        }

        return [$tags, $fields];
    }

    /**
     * Converts parameter string to an array.
     *
     *  In: name="test" comment="This is a test"
     *  Out: ['name' => 'test', 'comment' => 'This is a test']
     * 
     * @param  [type] $string [description]
     * @return [type]         [description]
     */
    protected function processParamsRegex($string)
    {
        /**
         * Match key/value pairs
         *
         * (\w+)="((?:\\.|[^"\\]+)*|[^"]*)"
         */
        $regex = '/';
        $regex .= '(\w+)'; // Any word
        $regex .= '="'; // Equal sign and open quote

        $regex .= '('; // Capture
        $regex .= '(?:\\\\.|[^"\\\\]+)*'; // Include escaped quotes \"
        $regex .= '|[^"]'; // Or anything other than a quote
        $regex .= '*)'; // Capture value
        $regex .= '"';
        $regex .= '/';

        preg_match_all($regex, $string, $match);

        return $match;
    }

    /**
     * Performs a regex looking for a field type (key) and returns
     * an array where:
     *
     *  0 - The full tag definition, eg: {text name="test"}...{/text}
     *  1 - The tag parameters as a string, eg: name="test"
     *  2 - The default text inside the tag (optional), eg: ...
     *
     * @param  string $string
     * @param  string $tag
     * @return array
     */
    protected function processTagsRegex($string, $tag)
    {
        $open = preg_quote(Parser::CHAR_OPEN);
        $close = preg_quote(Parser::CHAR_CLOSE);

        /*
         * Match the opening tag:
         * 
         * {text something="value"}
         * {text\s([^}]+)}
         */
        $regexOpen = $open.$tag.'\s'; // Open
        $regexOpen .= '([^'.$close.']+)'; // All but Close tag
        $regexOpen .= $close; // Close

        /*
         * Match all that does not contain another opening tag:
         *
         * (((?!{text)[\s\S])*)
         */
        $regexContent = '('; // Capture
        $regexContent .= '(?:'; // Non capture (negative lookahead)
        $regexContent .= '(?!'.$open.$tag.')'; // Not Close tag
        $regexContent .= '[\s\S]'; // All multiline
        $regexContent .= ')'; // End non capture
        $regexContent .= '*)'; // Capture content

        /*
         * Match the closing tag:
         * 
         * {/text}
         */
        $regexClose = $open.'/'.$tag.$close; // Close

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

    /**
     * Internal method to merge singular tags with double tags (open/close)
     * @param  array $singles
     * @param  array $doubles
     * @return array
     */
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
