<?php namespace October\Rain\Support;

use Illuminate\Support\Str as StrHelper;

/**
 * String helper
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class Str extends StrHelper
{
    /**
     * Converts a string to a boolean.
     */
    public static function toBoolean($string)
    {
        return self::evalBoolean($string) === true;
    }

    /**
     * Checks if a string is a boolean and returns it, otherwise the plain string is returned.
     * True values: y, yes, true.
     * False values: n, no, false.
     */
    public static function evalBoolean($string)
    {
        switch (strtolower(trim($string))) {
            case 'y':
            case 'yes':
            case 'true':
                return true;

            case 'n':
            case 'no':
            case 'false':
                return false;

            default:
                return $string;
        }
    }

    /**
     * Converts a HTML array string to a PHP array. Empty values are removed.
     * HTML: user[location][city]
     * PHP:  ['user', 'location', 'city']
     * @param $string String to process
     * @return array
     */
    public static function evalHtmlArray($string)
    {
        $result = [$string];

        if (preg_match('/^([^\]]+)(?:\[(.+)\])+$/', $string, $matches)) {
            if (count($matches) < 2)
                return $result;

            $result = explode('][', $matches[2]);
            array_unshift($result, $matches[1]);
        }

        return array_filter($result);
    }

    /**
     * Removes HTML from a string
     * @param $string String to strip HTML from
     * @return string
     */
    public static function stripHtml($string)
    {
        return htmlspecialchars_decode(strip_tags($string));
    }

    /**
     * Limits HTML with specific length with a proper tag handling.
     * @param string $html HTML string to limit
     * @param int $maxLength String length to truncate at
     * @param  string  $end
     * @return string
     */
    public static function limitHtml($html, $maxLength, $end = '...')
    {
        $printedLength = 0;
        $position = 0;
        $tags = array();

        $re = '{</?([a-z]+)[^>]*>|&#?[a-zA-Z0-9]+;|[\x80-\xFF][\x80-\xBF]*}';

        $result = '';

        while ($printedLength < $maxLength && preg_match($re, $html, $match, PREG_OFFSET_CAPTURE, $position)) {
            list($tag, $tagPosition) = $match[0];

            $str = mb_substr($html, $position, $tagPosition - $position);
            if ($printedLength + StrHelper::length($str) > $maxLength) {
                $result .= mb_substr($str, 0, $maxLength - $printedLength) . $end;
                $printedLength = $maxLength;
                break;
            }

            $result .= $str;
            $printedLength += StrHelper::length($str);
            if ($printedLength >= $maxLength) {
                $result .= $end;
                break;
            }

            if ($tag[0] == '&' || ord($tag) >= 0x80) {
                $result .= $tag;
                $printedLength++;
            }
            else {
                $tagName = $match[1][0];
                if ($tag[1] == '/') {
                    $openingTag = array_pop($tags);
                    $result .= $tag;
                }
                else if ($tag[StrHelper::length($tag) - 2] == '/')
                    $result .= $tag;
                else {
                    $result .= $tag;
                    $tags[] = $tagName;
                }
            }

            $position = $tagPosition + StrHelper::length($tag);
        }

        if ($printedLength < $maxLength && $position < StrHelper::length($html))
            $result .= substr($html, $position, $maxLength - $printedLength);

        while (!empty($tags))
            $result .= sprintf('</%s>', array_pop($tags));

        return $result;
    }

    /**
     * Cleans HTML to prevent XSS attacks.
     * @param  [type] $html [description]
     * @return [type]         [description]
     */
    public static function cleanHtml($html)
    {
        // Fix &entity\n;
        $html = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $html);
        $html = preg_replace('#(&\#*\w+)[\x00-\x20]+;#u', "$1;", $html);
        $html = preg_replace('#(&\#x*)([0-9A-F]+);*#iu', "$1$2;", $html);
        $html = html_entity_decode($html, ENT_COMPAT, 'UTF-8');

        // Remove any attribute starting with "on" or xmlns
        $html = preg_replace('#(<[^>]+[\x00-\x20\"\'\/])(on|xmlns)[^>]*>#iUu', "$1>", $html);

        // Remove javascript: and vbscript: protocols
        $html = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu', '$1=$2nojavascript...', $html);
        $html = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu', '$1=$2novbscript...', $html);
        $html = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*-moz-binding[\x00-\x20]*:#Uu', '$1=$2nomozbinding...', $html);
        $html = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*data[\x00-\x20]*:#Uu', '$1=$2nodata...', $html);

        // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
        $html = preg_replace('#(<[^>]+[\x00-\x20\"\'\/])style[^>]*>#iUu', "$1>", $html);

        // Remove namespaced elements (we do not need them)
        $html = preg_replace('#</*\w+:\w[^>]*>#i', "", $html);

        // Remove really unwanted tags
        do {
            $oldHtml = $html;
            $html = preg_replace('#</*(applet|meta|xml|blink|link|style|script|embed|object|iframe|frame|frameset|ilayer|layer|bgsound|title|base)[^>]*>#i', "", $html);
        }
        while ($oldHtml !== $html);

        return $html;
    }

    /**
     * Converts line breaks to a standard \r\n pattern.
     */
    public static function normalizeEol($string)
    {
        return preg_replace('~\R~u', "\r\n", $string);
    }

    /**
     * Removes the starting slash from a class namespace \
     */
    public static function normalizeClassName($name)
    {
        if (is_object($name))
            $name = get_class($name);

        $name = '\\'.ltrim($name, '\\');
        return $name;
    }

    /**
     * Generates a class ID from either an object or a string of the class name.
     */
    public static function getClassId($name)
    {
        if (is_object($name))
            $name = get_class($name);

        $name = ltrim($name, '\\');
        $name = str_replace('\\', '_', $name);

        return strtolower($name);
    }

    /**
     * Obtains an object class name without namespaces
     */
    public static function getRealClass($name)
    {
        $name = static::normalizeClassName($name);

        if (preg_match('@\\\\([\w]+)$@', $name, $matches))
            $name = $matches[1];

        return $name;
    }
}