<?php namespace October\Rain\Html;

use Illuminate\Html\HtmlBuilder as HtmlBuilderBase;

/**
 * Html builder
 *
 * Extension of illuminate/html, injects a session key to each form opening.
 *
 * @package october\html
 * @author Alexey Bobkov, Samuel Georges
 */
class HtmlBuilder extends HtmlBuilderBase
{

    /**
     * Build a single attribute element.
     *
     * @param  string  $key
     * @param  string  $value
     * @return string
     */
    protected function attributeElement($key, $value)
    {
        if (is_numeric($key)) $key = $value;

        if (is_null($value))
            return;

        if (is_array($value))
            $value = substr(htmlspecialchars(json_encode($value), ENT_QUOTES, 'UTF-8'), 1, -1);

        return $key.'="'.e($value).'"';
    }

}