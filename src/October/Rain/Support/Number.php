<?php namespace October\Rain\Support;

/**
 * Numeric helper
 */
class Number
{
    /**
     * Generate a random number based on time 
     * @return int Random integer
     */
    public static function rand()
    {
        return str_replace('.', '', microtime(true));
    }
}