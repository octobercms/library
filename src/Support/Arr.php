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

    public static function fixKeysNumber($array, $recursive = false) {
        foreach ($array as $k => $val) {
            if (is_array($val) && $recursive) {
                $array[$k] = self::fixKeysNumber($val);
            } 
        } 
        return self::sortNumericKeys($array);
    }

    public static function sortNumericKeys($array) {
        $i=0;
        foreach($array as $k => $v) {
            if(is_int($k)) {
                $rtn[$i] = $v;
                $i++;
            } else {
                $rtn[$k] = $v;
            } //if
        } //foreach
        return $rtn;
    }

}