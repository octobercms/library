<?php namespace October\Rain\Html;

/**
 * Methods that may be useful for processing HTML tasks
 *
 * @package october\html
 * @author Alexey Bobkov, Samuel Georges
 */
class Helper
{
    /**
     * Converts a HTML array string to an identifier string.
     * HTML: user[location][city]
     * Result: user-location-city
     * @param $string String to process
     * @return string
     */
    public static function nameToId($string)
    {
        return rtrim(str_replace('--', '-', str_replace(['[', ']'], '-', $string)), '-');
    }

    /**
     * Converts a HTML named array string to a PHP array. Empty values are removed.
     * HTML: user[location][city]
     * PHP:  ['user', 'location', 'city']
     * @param $string String to process
     * @return array
     */
    public static function nameToArray($string)
    {
        $result = [$string];

        if (strpbrk($string, '[]') === false) {
            return $result;
        }

        if (preg_match('/^([^\]]+)(?:\[(.+)\])+$/', $string, $matches)) {
            if (count($matches) < 2) {
                return $result;
            }

            $result = explode('][', $matches[2]);
            array_unshift($result, $matches[1]);
        }

        $result = array_filter($result, function($val) { return strlen($val); });

        return $result;
    }

    /**
     * Reduces the field name hierarchy depth by $level levels.
     * country[city][0][street][0] turns into country[city][0] when reduced by 1 level;
     * country[city][0][street][0] turns into country when reduced by 2 levels;
     * etc.
     *
     * @param string $fieldName
     * @param int $level
     * @return string
     */
    public static function reduceNameHierarchy($fieldName, $level)
    {
        $formName = self::nameToArray($fieldName);
        $sliceLength = count($formName) - $level * 2;

        if ($sliceLength <= 1) {
            return $formName[0];
        }

        $formName = array_slice($formName, 0, $sliceLength);
        $formNameFirst = array_shift($formName);

        return $formNameFirst.'['.implode('][', $formName).']';
    }
}
