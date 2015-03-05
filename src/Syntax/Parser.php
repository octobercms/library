<?php namespace October\Rain\Syntax;

/**
 * Dynamic Syntax parser
 */
class Parser
{
    const CHAR_OPEN = '{';
    const CHAR_CLOSE = '}';

    /**
     * @var October\Rain\Syntax\FieldParser Field parser instance.
     */
    protected $fieldParser;

    /**
     * @var October\Rain\Syntax\TextParser Text parser instance.
     */
    protected $textParser;

    /**
     * Constructor
     * @param string $template Template to parse.
     */
    public function __construct($template = null)
    {
        if ($template) {
            $this->template = $template;
            $this->fieldParser = new FieldParser($template);
            $this->textParser = new TextParser;
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
     * Renders the template fields to their actual values
     * @param  array $vars
     * @param  array $options
     * @return string
     */
    public function render($vars = [], $options = [])
    {
        $vars = array_merge($this->getFieldValues(), (array) $vars);
        $this->textParser->setOptions($options);
        return $this->textParser->parseString($this->toView(), $vars);
    }

    /**
     * Returns the default field values defined in the template
     * @return array
     */
    public function getFieldValues()
    {
        return $this->fieldParser->getDefaultParams();
    }

    /**
     * Returns an array of all fields and their options.
     * @return array
     */
    public function toEditor()
    {
        return $this->fieldParser->getFields();
    }

    /**
     * Returns the template with fields replaced with Twig markup
     * @return string
     */
    public function toTwig()
    {
        return $this->toViewEngine('twig');
    }

    /**
     * Returns the template with fields replaced with the simple
     * templating engine used by the TextParser class.
     * @return string
     */
    public function toView()
    {
        return $this->toViewEngine('simple');
    }

    /**
     * Parses the template to a specific view engine (Twig, Simple)
     * @param  string $engine
     * @return string
     */
    protected function toViewEngine($engine)
    {
        $engine = ucfirst($engine);
        $template = $this->template;


        $tags = $this->fieldParser->getTags();
        foreach ($tags as $field => $tag) {
            $template = is_array($tag)
                ? $this->processRepeatingTag($engine, $template, $field, $tag)
                : $this->processTag($engine, $template, $field, $tag);
        }

        return $template;
    }

    protected function processRepeatingTag($engine, $template, $field, $tagDetails)
    {
        $params = $this->fieldParser->getFieldParams($field);
        $innerFields = array_get($params, 'fields', []);
        $innerTags = $tagDetails['tags'];
        $innerTemplate = $tagDetails['template'];

        /*
         * Replace all the inner tags
         */
        foreach ($innerTags as $innerField => $tagString) {
            $innerParams = array_get($innerFields, $innerField, []);
            $tagReplacement = $this->{'eval'.$engine.'ViewField'}($innerField, $innerParams, 'fields');
            $innerTemplate = str_replace($tagString, $tagReplacement, $innerTemplate);
        }

        /*
         * Replace the opening tag
         */
        $openTag = array_get($tagDetails, 'open', '{repeater}');
        $openReplacement = $engine == 'Twig' ? '{% for fields in '.$field.' %}' : '{'.$field.'}';
        $openReplacement = $openReplacement . PHP_EOL;
        $innerTemplate = str_replace($openTag, $openReplacement, $innerTemplate);

        /*
         * Replace the closing tag
         */
        $closeTag = array_get($tagDetails, 'close', '{/repeater}');
        $closeReplacement = $engine == 'Twig' ? '{% endfor %}' : '{/'.$field.'}';
        $closeReplacement = PHP_EOL . $closeReplacement;
        $innerTemplate = str_replace($closeTag, $closeReplacement, $innerTemplate);

        $templateString = $tagDetails['template'];
        $template = str_replace($templateString, $innerTemplate, $template);
        return $template;
    }

    protected function processTag($engine, $template, $field, $tagString)
    {
        $params = $this->fieldParser->getFieldParams($field);
        $tagReplacement = $this->{'eval'.$engine.'ViewField'}($field, $params);
        $template = str_replace($tagString, $tagReplacement, $template);
        return $template;
    }

    /**
     * Processes a field type and converts it to the Twig engine.
     * @param  string $field
     * @param  array $params
     * @return string
     */
    protected function evalTwigViewField($field, $params, $prefix = null)
    {
        $type = isset($params['type']) ? $params['type'] : 'text';

        if ($prefix) {
            $field = $prefix.'.'.$field;
        }

        switch ($type) {
            default:
            case 'text':
            case 'textarea':
                $result = '{{ ' . $field . ' }}';
                break;
            case 'markdown':
                $result = '{{ ' . $field . '|md }}';
                break;
            case 'richeditor':
                $result = '{{ ' . $field . '|raw }}';
                break;
        }

        return $result;
    }

    /**
     * Processes a field type and converts it to the Simple engine.
     * @param  string $field
     * @param  array $params
     * @return string
     */
    protected function evalSimpleViewField($field, $params, $prefix = null)
    {
        $type = isset($params['type']) ? $params['type'] : 'text';

        switch ($type) {
            default:
                $result = static::CHAR_OPEN . $field . static::CHAR_CLOSE;
                break;
        }

        return $result;
    }
}
