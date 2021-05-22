<?php namespace October\Rain\Support;

use Illuminate\Support\Collection as CollectionBase;

/**
 * Collection is an umbrealla class for Laravel's Collection
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class Collection extends CollectionBase
{
    /**
     * lists get an array with the values of a given key
     * @param  string  $value
     * @param  string  $key
     * @return array
     */
    public function lists($value, $key = null)
    {
        return $this->pluck($value, $key)->all();
    }
}
