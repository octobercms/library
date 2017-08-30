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
    public static function build($array, callable $callback)
    {
        $results = [];

        foreach ($array as $key => $value) {
            list($innerKey, $innerValue) = call_user_func($callback, $key, $value);

            $results[$innerKey] = $innerValue;
        }

        return $results;
    }
}
