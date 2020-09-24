<?php namespace October\Rain\Support;

use Illuminate\Support\Arr as ArrHelper;

/**
 * Array helper
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class Arr extends ArrHelper
{
    /**
     * Build a new array using a callback.
     *
     * @param  array  $array
     * @param  callable  $callback
     * @return array
     */
    public static function build(array $array, callable $callback)
    {
        $results = [];

        foreach ($array as $key => $value) {
            list($innerKey, $innerValue) = call_user_func($callback, $key, $value);

            $results[$innerKey] = $innerValue;
        }

        return $results;
    }

    /**
     * Transform a dot-notated array into a normal array.
     *
     * Courtesy of https://github.com/laravel/framework/issues/1851#issuecomment-20796924
     *
     * @param array $dotArray
     * @return array
     */
    public static function undot(array $dotArray)
    {
        $array = [];

        foreach ($dotArray as $key => $value) {
            static::set($array, $key, $value);
        }

        return $array;
    }
}
