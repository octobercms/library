<?php namespace October\Rain\Support;

use ParsedownExtra;

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
        return ParsedownExtra::instance()->text($text);
    }
}
