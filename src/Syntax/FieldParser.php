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
        'textarea',
        'fileupload'
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
        $tags = [];
        $fields = [];

        $result = $this->processTagsRegex($template, $this->registeredTags);
        $tagStrings = $result[0];
        $tagNames = $result[1];
        $paramStrings = $result[2];

        foreach ($tagStrings as $key => $tagString) {
            $params = $this->processParams($paramStrings[$key]);

            if (isset($params['name'])) {
                $name = $params['name'];
                unset($params['name']);
            }
            else {
                $name = md5($tagString);
            }

            $params['type'] = $tagNames[$key];

            $tags[$name] = $tagString;
            $fields[$name] = $params;
        }

        $this->tags = $this->tags + $tags;
        $this->fields = $this->fields + $fields;

        return [$tags, $fields];
    }

    /**
     * Processes group 2 from the Tag regex and returns
     * an array of captured parameters.
     * @param  string $value
     * @return array
     */
    protected function processParams($value)
    {
        $close = Parser::CHAR_CLOSE;
        $closePos = strpos($value, $close);
        $defaultValue = '';
        if ($closePos === false) {
            $paramString = $value;
        }
        elseif (substr($value, -1) == $close) {
            $paramString = substr($value, 0, -1);
        }
        else {
            $paramString = substr($value, 0, $closePos);
            $defaultValue = trim(substr($value, $closePos + 1));
        }

        $result = $this->processParamsRegex($paramString);
        $paramNames = $result[1];
        $paramValues = $result[2];
        $params = array_combine($paramNames, $paramValues);
        $params['default'] = $defaultValue;

        return $params;
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
        /*
         * Match key/value pairs
         *
         * (\w+)="((?:\\.|[^"\\]+)*|[^"]*)"
         */
        $regex = '#';
        $regex .= '(\w+)'; // Any word
        $regex .= '="'; // Equal sign and open quote

        $regex .= '('; // Capture
        $regex .= '(?:\\\\.|[^"\\\\]+)*'; // Include escaped quotes \"
        $regex .= '|[^"]'; // Or anything other than a quote
        $regex .= '*)'; // Capture value
        $regex .= '"';
        $regex .= '#';

        preg_match_all($regex, $string, $match);

        return $match;
    }

    /**
     * Performs a regex looking for a field type (key) and returns
     * an array where:
     *
     *  0 - The full tag definition, eg: {text name="test"}Foobar{/text}
     *  1 - The opening and closing tag name
     *  2 - The tag parameters as a string, eg: name="test"} and;
     *  2 - The default text inside the tag (optional), eg: Foobar
     * 
     * @param  string $string
     * @param  string $tag
     * @return array
     */
    protected function processTagsRegex($string, $tags)
    {
        /*
         * Match opening and close tags
         *
         * {(text|textarea)\s([\S\s]+?){/(?:\1)}
         */
        $open = preg_quote(Parser::CHAR_OPEN);
        $close = preg_quote(Parser::CHAR_CLOSE);
        $tags = implode('|', $tags);

        $regex = '#';
        $regex .= $open.'('.$tags.')\s'; // Group 1
        $regex .= '([\S\s]+?)'; // Group 2 (Non greedy)
        $regex .= $open.'/(?:\1)'.$close; // Group X (Not captured)
        $regex .= '#';

        preg_match_all($regex, $string, $match);

        return $match;
    }

}
