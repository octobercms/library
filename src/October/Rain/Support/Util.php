<?php namespace October\Rain\Support;

/**
 * General Utility helper
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class Util
{

    /**
     * Extension of PHPs array_merge() except supporting 
     * an array of arrays to merge.
     *
     * Usage:
     *   Util::arrayMerge([$arr1, $arr2, $arr3]);
     *   Util::arrayMerge($arr1, $arr2, $arr3);
     */
    public static function arrayMerge($collection)
    {
        if (func_num_args() > 1)
            $collection = func_get_args();
        
        return call_user_func_array('array_merge', $collection);
    }

}