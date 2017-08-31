<?php namespace October\Rain\Support;

use Illuminate\Support\Collection as CollectionBase;

/**
 * Proxy class.
 */
class Collection extends CollectionBase
{
    /**
     * Get an array with the values of a given key.
     *
     * @param  string  $value
     * @param  string  $key
     * @return array
     */
    public function lists($value, $key = null)
    {
        return $this->pluck($value, $key)->all();
    }
}
