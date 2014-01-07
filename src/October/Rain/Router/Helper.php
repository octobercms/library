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

        $result = array();
        foreach ($segments as $segment) {
            if (strlen($segment))
                $result[] = $segment;
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
            if (strlen($segment))
                $url .= '/'.trim($segment);
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
        $defaultColumns = ['id'];
        foreach ($columns as $column) {
            if (!isset($object->{$column}))
                continue;

            $string = str_replace(':'.$column, $object->{$column}, $string);
        }

        return $string;
    }
}