<?php namespace October\Rain\Router;

/**
 * Helper methods that may be useful for processing routing activity
 *
 * @package october\router
 * @author Alexey Bobkov, Samuel Georges
 */
class Helper
{
    /**
     * validateUrl checks if the URL pattern provided is valid for parsing
     */
    public static function validateUrl(string $url): bool
    {
        if ($url && $url[0] !== '/') {
            return false;
        }

        $segments = static::segmentizeUrl($url);

        foreach ($segments as $segment) {
            // Remove regex portion
            $cleanSegment = explode('|', $segment)[0];

            // Validate segment
            if (!preg_match(
                '/^[a-z0-9\/\:_\-\*\[\]\+\?\.\^\\\$]*$/i',
                $cleanSegment
            )) {
                return false;
            }
        }

        return true;
    }

    /**
     * normalizeUrl adds leading slash and removes trailing slash from the URL.
     *
     * @param string $url URL to normalize.
     * @return string Returns normalized URL.
     */
    public static function normalizeUrl($url)
    {
        if (substr($url, 0, 1) !== '/') {
            $url = '/' . $url;
        }

        if (substr($url, -1) === '/') {
            $url = substr($url, 0, -1);
        }

        if (!strlen($url)) {
            $url = '/';
        }

        return $url;
    }

    /**
     * segmentizeUrl splits a URL by segments separated by the slash symbol
     * and returns the URL segments. Using a pattern includes regex support
     * in the URL.
     *
     * @param string $url
     * @param bool $pattern
     * @return array
     */
    public static function segmentizeUrl($url, $pattern = true)
    {
        $url = self::normalizeUrl($url);

        if ($pattern) {
            $segments = preg_split("#(?<!\\\)/#", $url);
        } else {
            $segments = explode('/', $url);
        }

        $result = [];
        foreach ($segments as $segment) {
            if (strlen($segment)) {
                $result[] = $segment;
            }
        }

        return $result;
    }

    /**
     * rebuildUrl from an array of segments.
     *
     * @param array $urlArray Array the URL segments.
     * @return string Returns rebuilt URL.
     */
    public static function rebuildUrl(array $urlArray)
    {
        $url = '';
        foreach ($urlArray as $segment) {
            if (strlen($segment)) {
                $url .= '/' . trim($segment);
            }
        }

        return self::normalizeUrl($url);
    }

    /**
     * parseValues replaces :column_name with it's object value.
     * Example: /some/link/:id/:name -> /some/link/1/Joe
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

        foreach ($columns as $column) {
            if (
                !isset($object->{$column}) ||
                is_array($object->{$column}) ||
                (is_object($object->{$column}) && !method_exists($object->{$column}, '__toString'))
            ) {
                continue;
            }

            $string = str_replace(':' . $column, urlencode((string) $object->{$column}), $string);
        }

        return $string;
    }

    /**
     * replaceParameters replaces :column_name with object value without requiring a
     * list of names. Example: /some/link/:id/:name -> /some/link/1/Joe
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
     * segmentIsWildcard checks whether an URL pattern segment is a wildcard.
     * @param string $segment The segment definition.
     * @return boolean Returns boolean true if the segment is a wildcard. Returns false otherwise.
     */
    public static function segmentIsWildcard($segment)
    {
        $name = mb_substr($segment, 1);

        $wildMarkerPos = mb_strpos($name, '*');
        if ($wildMarkerPos === false) {
            return false;
        }

        $regexMarkerPos = mb_strpos($name, '|');
        if ($regexMarkerPos === false) {
            return true;
        }

        if ($wildMarkerPos !== false && $regexMarkerPos !== false) {
            return $wildMarkerPos < $regexMarkerPos;
        }

        return true;
    }

    /**
     * segmentIsOptional checks whether an URL pattern segment is optional.
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
     * getParameterName extracts the parameter name from a URL pattern segment definition.
     * @param string $segment
     * @return string
     */
    public static function getParameterName($segment)
    {
        $name = mb_substr($segment, 1);

        $regexMarkerPos = mb_strpos($name, '|');
        if ($regexMarkerPos !== false) {
            $name = mb_substr($name, 0, $regexMarkerPos);
        }

        $optMarkerPos = mb_strpos($name, '?');
        if ($optMarkerPos !== false) {
            $name = mb_substr($name, 0, $optMarkerPos);
        }

        $wildMarkerPos = mb_strpos($name, '*');
        if ($wildMarkerPos !== false) {
            $name = mb_substr($name, 0, $wildMarkerPos);
        }

        return $name;
    }

    /**
     * getSegmentRegExp extracts the regular expression from a URL pattern segment definition.
     * @param string $segment The segment definition.
     * @return string Returns the regular expression string or false if the expression is not defined.
     */
    public static function getSegmentRegExp($segment)
    {
        if (($pos = mb_strpos($segment, '|')) !== false) {
            $regexp = mb_substr($segment, $pos + 1);
            if (!mb_strlen($regexp)) {
                return false;
            }

            return '/' . $regexp . '/';
        }

        return false;
    }

    /**
     * getSegmentDefaultValue extracts the default parameter value from a URL pattern
     * segment definition.
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
        $wildMarkerPos = mb_strpos($segment, '*');
        $value = false;

        if ($regexMarkerPos !== false) {
            $value = mb_substr($segment, $optMarkerPos + 1, $regexMarkerPos - $optMarkerPos - 1);
        } elseif ($wildMarkerPos !== false) {
            $value = mb_substr($segment, $optMarkerPos + 1, $wildMarkerPos - $optMarkerPos - 1);
        } else {
            $value = mb_substr($segment, $optMarkerPos + 1);
        }

        return strlen($value) ? $value : false;
    }
}
