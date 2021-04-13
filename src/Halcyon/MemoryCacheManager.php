<?php namespace October\Rain\Halcyon;

use App;
use Config;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Store;

/**
 * MemoryCacheManager
 */
class MemoryCacheManager extends CacheManager
{
    /**
     * repository returns the memory repo
     */
    public function repository(Store $store)
    {
        return new MemoryRepository($store);
    }

    /**
     * isEnabled returns true if cache manager is enabled via config
     */
    public static function isEnabled(): bool
    {
        $enabled = Config::get('system.in_memory_cache', null);

        if ($enabled === null) {
            $enabled = !App::runningInConsole();
        }

        return $enabled;
    }
}
