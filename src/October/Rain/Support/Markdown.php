<?php namespace October\Rain\Support;

use Parsedown;

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
        return Parsedown::instance()->parse($text);
    }
}
