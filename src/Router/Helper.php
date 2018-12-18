<?php namespace October\Rain\Router;

/**
 * Methods that may be useful for processing routing activity
 *
 * @package october\router
 * @author Alexey Bobkov, Samuel Georges
 */
class Helper
{
    /**
     * Adds leading slash and removes trailing slash from the URL.
     *
     * @param string $url URL to normalize.
     * @return string Returns normalized URL.
     */
    public static function normalizeUrl($url)
    {
        if (substr($url, 0, 1) != '/')
            $url = '/'.$url;

        if (substr($url, -1) == '/')
            $url = substr($url, 0, -1);

        if (!strlen($url))
            $url = '/';

        return $url;
    }

    /**
     * Splits an URL by segments separated by the slash symbol.
     *
     * @param string $url URL to segmentize.
     * @return array Returns the URL segments.
     */
    public static function segmentizeUrl($url)
    {
        $url = self::normalizeUrl($url);
        $segments = explode('/', $url);

        $result = [];
        foreach ($segments as $segment) {
            if (strlen($segment)) {
                $result[] = $segment;
            }
        }

        return $result;
    }

    /**
     * Rebuilds a URL from an array of segments.
     *
     * @param array $urlArray Array the URL segments.
     * @return string Returns rebuilt URL.
     */
    public static function rebuildUrl(array $urlArray)
    {
        $url = '';
        foreach ($urlArray as $segment) {
            if (strlen($segment)) {
                $url .= '/'.trim($segment);
            }
        }

        return self::normalizeUrl($url);
    }

    /**
     * Replaces :column_name with it's object value. Example: /some/link/:id/:name -> /some/link/1/Joe
     *
     * @param stdObject $object Object containing the data
     * @param array $columns Expected key names to parse
     * @param string $string URL template
     * @return string Built string
     */
    public static function parseValues($object, array $columns, $string)
    {
        if (is_array($object)) {
            $object = (object) $object;
        }

        $defaultColumns = ['id'];
        foreach ($columns as $column) {
            if (
                !isset($object->{$column}) ||
                is_array($object->{$column}) ||
                (is_object($object->{$column}) && !method_exists($object->{$column}, '__toString'))
            ) {
                continue;
            }

            $string = str_replace(':'.$column, urlencode((string) $object->{$column}), $string);
        }

        return $string;
    }

    /**
     * Replaces :column_name with object value without requiring a list of names. Example: /some/link/:id/:name -> /some/link/1/Joe
     *
     * @param stdObject $object Object containing the data
     * @param string $string URL template
     * @return string Built string
     */
    public static function replaceParameters($object, $string)
    {
        if (preg_match_all('/\:([\w]+)/', $string, $matches)) {
            return self::parseValues($object, $matches[1], $string);
        }

        return $string;
    }

    /**
     * Checks whether an URL pattern segment is a wildcard.
     * @param string $segment The segment definition.
     * @return boolean Returns boolean true if the segment is a wildcard. Returns false otherwise.
     */
    public static function segmentIsWildcard($segment)
    {
        return mb_strpos($segment, ':') === 0 && mb_substr($segment, -1) === '*';
    }

    /**
     * Checks whether an URL pattern segment is optional.
     * @param string $segment The segment definition.
     * @return boolean Returns boolean true if the segment is optional. Returns false otherwise.
     */
    public static function segmentIsOptional($segment)
    {
        $name = mb_substr($segment, 1);

        $optMarkerPos = mb_strpos($name, '?');
        if ($optMarkerPos === false) {
            return false;
        }

        $regexMarkerPos = mb_strpos($name, '|');
        if ($regexMarkerPos === false) {
            return true;
        }

        if ($optMarkerPos !== false && $regexMarkerPos !== false) {
            return $optMarkerPos < $regexMarkerPos;
        }

        return false;
    }

    /**
     * Extracts the parameter name from a URL pattern segment definition.
     * @param string $segment The segment definition.
     * @return string Returns the segment name.
     */
    public static function getParameterName($segment)
    {
        $name = mb_substr($segment, 1);

        $optMarkerPos = mb_strpos($name, '?');
        $wildMarkerPos = mb_strpos($name, '*');
        $regexMarkerPos = mb_strpos($name, '|');

        if ($wildMarkerPos !== false) {
            return mb_substr($name, 0, $wildMarkerPos);
        }

        if ($optMarkerPos !== false && $regexMarkerPos !== false) {
            if ($optMarkerPos < $regexMarkerPos) {
                return mb_substr($name, 0, $optMarkerPos);
            }

            return mb_substr($name, 0, $regexMarkerPos);
        }

        if ($optMarkerPos !== false) {
            return mb_substr($name, 0, $optMarkerPos);
        }

        if ($regexMarkerPos !== false) {
            return mb_substr($name, 0, $regexMarkerPos);
        }

        return $name;
    }

    /**
     * Extracts the regular expression from a URL pattern segment definition.
     * @param string $segment The segment definition.
     * @return string Returns the regular expression string or false if the expression is not defined.
     */
    public static function getSegmentRegExp($segment)
    {
        if (($pos = mb_strpos($segment, '|')) !== false) {
            $regexp = mb_substr($segment, $pos+1);
            if (!mb_strlen($regexp)) {
                return false;
            }

            return '/'.$regexp.'/';
        }

        return false;
    }

    /**
     * Extracts the default parameter value from a URL pattern segment definition.
     * @param string $segment The segment definition.
     * @return string Returns the default value if it is provided. Returns false otherwise.
     */
    public static function getSegmentDefaultValue($segment)
    {
        $optMarkerPos = mb_strpos($segment, '?');
        if ($optMarkerPos === false) {
            return false;
        }

        $regexMarkerPos = mb_strpos($segment, '|');
        $value = false;

        if ($regexMarkerPos !== false) {
            $value = mb_substr($segment, $optMarkerPos+1, $regexMarkerPos-$optMarkerPos-1);
        }
        else {
            $value = mb_substr($segment, $optMarkerPos+1);
        }

        return strlen($value) ? $value : false;
    }

}
