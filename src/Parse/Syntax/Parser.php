<?php namespace October\Rain\Parse\Syntax;

use October\Rain\Parse\Bracket as TextParser;

/**
 * Dynamic Syntax parser
 */
class Parser
{
    const CHAR_OPEN = '{';
    const CHAR_CLOSE = '}';

    /**
     * @var \October\Rain\Parse\Syntax\FieldParser Field parser instance.
     */
    protected $fieldParser;

    /**
     * @var \October\Rain\Parse\Bracket Text parser instance.
     */
    protected $textParser;

    /**
     * @var string A prefix to place before all variable references
     * when rendering the view.
     */
    protected $varPrefix = '';

    /**
     * Constructor.
     * Available options:
     * - varPrefix: Prefix to add to every top level parameter.
     * - tagPrefix: Prefix to add to all tags, in addition to tags without a prefix.
     * @param array $options
     * @param string $template Template to parse.
     */
    public function __construct($template = null, $options = [])
    {
        if ($template) {
            $this->template = $template;
            $this->varPrefix = array_get($options, 'varPrefix', '');
            $this->fieldParser = new FieldParser($template, $options);

            $textFilters = [
                'md' => ['Markdown', 'parse'],
                'media' => ['System\Classes\MediaLibrary', 'url']
            ];

            $this->textParser = new TextParser(['filters' => $textFilters]);
        }
    }

    /**
     * Static helper for new instances of this class.
     * @param  string $template
     * @param  array $options
     * @return self
     */
    public static function parse($template, $options = [])
    {
        return new static($template, $options);
    }

    /**
     * Renders the template fields to their actual values
     * @param  array $vars
     * @param  array $options
     * @return string
     */
    public function render($vars = [], $options = [])
    {
        $vars = array_replace_recursive($this->getFieldValues(), (array) $vars);
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
        $prefixField = $this->varPrefix.$field;
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
        $openReplacement = $engine == 'Twig' ? '{% for fields in '.$prefixField.' %}' : '{'.$prefixField.'}';
        $openReplacement = $openReplacement . PHP_EOL;
        $innerTemplate = str_replace($openTag, $openReplacement, $innerTemplate);

        /*
         * Replace the closing tag
         */
        $closeTag = array_get($tagDetails, 'close', '{/repeater}');
        $closeReplacement = $engine == 'Twig' ? '{% endfor %}' : '{/'.$prefixField.'}';
        $closeReplacement = PHP_EOL . $closeReplacement;
        $innerTemplate = str_replace($closeTag, $closeReplacement, $innerTemplate);

        $templateString = $tagDetails['template'];
        $template = str_replace($templateString, $innerTemplate, $template);
        return $template;
    }

    protected function processTag($engine, $template, $field, $tagString)
    {
        $prefixField = $this->varPrefix.$field;
        $params = $this->fieldParser->getFieldParams($field);
        $tagReplacement = $this->{'eval'.$engine.'ViewField'}($prefixField, $params);
        $template = str_replace($tagString, $tagReplacement, $template);
        return $template;
    }

    /**
     * Processes a field type and converts it to the Twig engine.
     * @param  string $field
     * @param  array $params
     * @param  string $prefix
     * @return string
     */
    protected function evalTwigViewField($field, $params, $prefix = null)
    {
        if (isset($params['X_OCTOBER_IS_VARIABLE'])) {
            return '';
        }

        /*
         * Used by Twig for loop
         */
        if ($prefix) {
            $field = $prefix.'.'.$field;
        }

        $type = $params['type'] ?? 'text';

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
            case 'mediafinder':
                $result = '{{ ' . $field . '|media }}';
                break;
            case 'checkbox':
                $result = '{% if ' . $field . ' %}' . $params['_content'] . '{% endif %}';
                break;
            case 'datepicker':
                switch($params['mode']) {
                    default:
                    case 'datetime':
                        $result = '{{ ' . $field . '|date("Y-m-d H:i:s") }}';
                        break;
                    case 'date':
                        $result = '{{ ' . $field . '|date("Y-m-d") }}';
                        break;
                    case 'time':
                        $result = '{{ ' . $field . '|date("H:i:s") }}';
                        break;
                }
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
        if (isset($params['X_OCTOBER_IS_VARIABLE'])) {
            return '';
        }

        $type = $params['type'] ?? 'text';

        switch ($type) {
            case 'markdown':
                $result = static::CHAR_OPEN . $field . '|md' . static::CHAR_CLOSE;
                break;
            case 'mediafinder':
                $result = static::CHAR_OPEN . $field . '|media' . static::CHAR_CLOSE;
                break;
            case 'checkbox':
                $result = static::CHAR_OPEN . '?' . $field . static::CHAR_CLOSE;
                $result .= $params['_content'];
                $result .= static::CHAR_OPEN . '/' . $field . static::CHAR_CLOSE;
                break;
            default:
                $result = static::CHAR_OPEN . $field . static::CHAR_CLOSE;
                break;
        }

        return $result;
    }
}
