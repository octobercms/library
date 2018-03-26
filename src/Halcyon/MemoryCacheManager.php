<?php namespace October\Rain\Halcyon;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Store;

class MemoryCacheManager extends CacheManager
{
    public function repository(Store $store)
    {
        $repository = new MemoryRepository($store);
        return $repository;
    }

    public static function isEnabled()
    {
        return true;
    }
}
