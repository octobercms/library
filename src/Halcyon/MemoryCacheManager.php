<?php namespace October\Rain\Halcyon;

use App;
use Config;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Store;

class MemoryCacheManager extends CacheManager
{
    public function repository(Store $store)
    {
        return new MemoryRepository($store);
    }

    public static function isEnabled()
    {
        $disabled = Config::get('cache.disableRequestCache', null);
        if ($disabled === null) {
            return !App::runningInConsole();
        }

        return !$disabled;
    }
}
