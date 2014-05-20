<?php namespace October\Rain\Support;

/**
 * Markdown content parser
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class Markdown
{
    /**
     * Parse text using Markdown and Markdown-Extra
     * @param  string $text Mardown text to parse
     * @return string       Resulting HTML
     */
    public static function parse($text)
    {
        $instance = new ParsedownExtraOctober;
        return $instance->text($text);
    }
}
