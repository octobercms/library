<?php namespace October\Rain\View;

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
