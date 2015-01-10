<?php namespace October\Rain\Support;

/**
 * Helper class for passing partially parsed Markdown input
 * to and from the markdown.beforeParse and markdown.parse
 * even handlers
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class MarkdownData
{
    public function __construct($text) {
        $this->text = $text;
    }

    var $text;
}