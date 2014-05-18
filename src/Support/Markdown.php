<?php namespace October\Rain\Support;

use \Michelf\MarkdownExtra as MD;

/**
 * Markdown content parser
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class Markdown
{
    public static function parse($text)
    {
        return MD::defaultTransform($text);
    }
}
