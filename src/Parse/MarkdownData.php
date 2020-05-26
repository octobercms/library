<?php namespace October\Rain\Parse;

/**
 * Helper class for passing partially parsed Markdown input
 * to and from the markdown.beforeParse and markdown.parse
 * event handlers
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class MarkdownData
{
    /**
     * @var string
     */
    public $text;

    public function __construct($text)
    {
        $this->text = $text;
    }
}
