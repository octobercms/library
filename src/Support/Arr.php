<?php namespace October\Rain\Support;

use Lang;
use Illuminate\Support\Arr as ArrHelper;

/**
 * Arr helper as an extension to Laravel
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class Arr extends ArrHelper
{
    /**
     * build a new array using a callback.
     */
    public static function build($array, callable $callback): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            list($innerKey, $innerValue) = call_user_func($callback, $key, $value);

            $results[$innerKey] = $innerValue;
        }

        return $results;
    }

    /**
     * trans will translate an array, usually for dropdown and checkboxlist options
     */
    public static function trans(array $arr): array
    {
        foreach ($arr as &$val) {
            if (is_array($val)) {
                $val = array_map([Lang::class, 'get'], $val);
            }
            else {
                $val = Lang::get($val);
            }
        }

        return $arr;
    }
}
