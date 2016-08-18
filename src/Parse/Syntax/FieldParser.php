<?php namespace October\Rain\Parse\Syntax;

use Exception;

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
     * @var string A prefix to place before all tag references
     * eg: {namespace:text}{/namespace:text}
     */
    protected $tagPrefix = '';

    /**
     * @var array Registered template tags
     */
    protected $registeredTags = [
        'text',
        'textarea',
        'richeditor',
        'markdown',
        'fileupload',
        'mediafinder',
        'dropdown',
        'radio',
        'repeater',
        'variable'
    ];

    /**
     * Constructor
     * @param string $template Template to parse.
     */
    public function __construct($template = null, $options = [])
    {
        if ($template) {
            $this->tagPrefix = array_get($options, 'tagPrefix', '');
            $this->template = $template;
            $this->processTemplate($template);
        }
    }

    /**
     * Processes repeating tags first, then registered tags and assigns
     * the results to local object properties.
     * @return void
     */
    protected function processTemplate($template)
    {
        // Process all tags
        list($tags, $fields) = $this->processAllTags($template);

        $this->tags = $this->tags + $tags;
        $this->fields = $this->fields + $fields;
    }

    /**
     * Static helper for new instances of this class.
     * @param string $template
     * @param array $options
     * @return FieldParser
     */
    public static function parse($template, $options = [])
    {
        return new static($template, $options);
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
     * Returns tag strings for a specific field
     * @param  string $field
     * @return array
     */
    public function getFieldTags($field)
    {
        return isset($this->tags[$field])
            ? $this->tags[$field]
            : [];
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
    public function getDefaultParams($fields = null)
    {
        if (!$fields) {
            $fields = $this->fields;
        }

        $defaults = [];

        foreach ($fields as $field => $params) {
            if ($params['type'] == 'repeater') {
                $defaults[$field] = [];
                $defaults[$field][] = $this->getDefaultParams(array_get($params, 'fields', []));
            }
            else {
                $defaults[$field] = isset($params['default']) ? $params['default'] : null;
            }
        }

        return $defaults;
    }

    /**
     * Processes all tags against a template.
     * @param  string $template
     * @return void
     */
    protected function processAllTags($template)
    {
        list($repeatTags, $repeatFields) = $this->processTags($template, ['repeater']);

        foreach ($repeatFields as $name => $field) {
            $outerTemplate = $repeatTags[$name];
            $innerTemplate = $field['default'];
            unset($field['default']);

            list($innerTags, $innerFields) = $this->processAllTags($innerTemplate);

            list($openTag, $closeTag) = explode($innerTemplate, $outerTemplate);
            $field['fields'] = $innerFields;
            $repeatTags[$name] = [
                'tags'     => $innerTags,
                'template' => $outerTemplate,
                'open'     => $openTag,
                'close'    => $closeTag
            ];

            // Remove the inner content of the repeater
            // tag to prevent further parsing
            $template = str_replace($outerTemplate, $openTag.$closeTag, $template);
            $repeatFields[$name] = $field;
        }

        list($tags, $fields) = $this->processTags($template);

        /*
         * Layer the repeater tags over the standard ones to retain
         * the original sort order
         */
        foreach ($repeatFields as $field => $params) {
            $fields[$field] = $params;
        }

        foreach ($repeatTags as $field => $params) {
            $tags[$field] = $params;
        }

        return [$tags, $fields];
    }

    /**
     * Processes all registered tags against a template.
     * @param  string $template
     * @return void
     */
    protected function processTags($template, $usingTags = null)
    {
        if (!$usingTags) {
            $usingTags = $this->registeredTags;
        }

        if ($this->tagPrefix) {
            foreach ($usingTags as $tag) {
                $usingTags[] = $this->tagPrefix . $tag;
            }
        }

        $tags = [];
        $fields = [];

        $result = $this->processTagsRegex($template, $usingTags);
        $tagStrings = $result[0];
        $tagNames = $result[1];
        $paramStrings = $result[2];

        foreach ($tagStrings as $key => $tagString) {
            $params = $this->processParams($paramStrings[$key]);
            $tagName = $tagNames[$key];

            if (isset($params['name'])) {
                $name = $params['name'];
                unset($params['name']);
            }
            else {
                $name = md5($tagString);
            }

            if ($tagName == 'variable') {
                $params['X_OCTOBER_IS_VARIABLE'] = true;
                $tagName = array_get($params, 'type', 'text');
            }
            else {
                $params['type'] = $tagName;
            }

            if (in_array($tagName, ['dropdown', 'radio']) && isset($params['options'])) {
                $params['options'] = $this->processOptionsToArray($params['options']);
            }

            $tags[$name] = $tagString;
            $fields[$name] = $params;
        }

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
     * @param  string $tags
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

        $re_opentag = $open.'('.$tags.')\s';
        $re_closetag = $open.'/(?:\1)'.$close;

        $regex = '#
                  ' . $re_opentag . '
                     ((?>
                          (?>  (?!' . $re_opentag . '|' . $re_closetag . ') .  )+
                       |  (?R)
                     )*)
                  ' . $re_closetag . '
               #sxi';

        preg_match_all($regex, $string, $match);

        return $match;
    }
    
    /**
     * Splits an option string to an array.
     *
     * one|two           -> [one, two]
     * one:One|two:Two   -> [one => 'One', two => 'Two']
     *
     * @param  string $optionsString
     * @return array
     */
    protected function processOptionsToArray($optionsString)
    {
        $options = explode('|', $optionsString);

        $result = [];
        foreach ($options as $index => $optionStr) {
            $parts = explode(':', $optionStr, 2);

            if (count($parts) > 1 ) {
                $key = trim($parts[0]);

                if (strlen($key)) {
                    if (!preg_match('/^[0-9a-z-_]+$/i', $key)) {
                        throw new Exception(sprintf('Invalid drop-down option key: %s. Option keys can contain only digits, Latin letters and characters _ and -', $key));
                    }

                    $result[$key] = trim($parts[1]);
                }
                else {
                    $result[$index] = trim($optionStr);
                }
            }
            else {
                $result[$index] = trim($optionStr);
            }
        }

        return $result;
    }

}
