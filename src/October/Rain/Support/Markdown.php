<?php namespace October\Rain\Support;

use Parsedown;

/**
 * Markdown content parser
 */
class Markdown
{
    public static function parse($text)
    {
        return Parsedown::instance()->parse($text);
    }
}
