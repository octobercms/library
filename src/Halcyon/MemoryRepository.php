<?php namespace October\Rain\Halcyon;

use Illuminate\Cache\Repository;

/**
 * Provides a simple request-level cache.
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class MemoryRepository extends Repository
{
    /**
     * Values stored in memory
     *
     * @var array
     */
    protected $cache = [];

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string|array $key
     * @param  mixed        $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if(is_array($key)) {
            return $this->many($key);
        }

        $value = $this->getFromMemoryCache($key);

        if(!is_null($value)) {
            return $value;
        }

        $value = parent::get($key, $default);
        $this->putInMemoryCache($key, $value);

        return $value;
    }

    /**
     * Store an item in the cache.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @param  \DateTimeInterface|\DateInterval|float|int  $minutes
     * @return void
     */
    public function put($key, $value, $minutes = null)
    {
        if (is_array($key)) {
            $this->putMany($key, $value);
        }

        if (!is_null($minutes = $this->getMinutes($minutes))) {
            $this->putInMemoryCache($key, $value);
            parent::put($key, $value, $minutes);
        }
    }

    /**
     * Increment the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function increment($key, $value = 1)
    {
        $newValue = parent::increment($key, $value);
        $this->putInMemoryCache($key, $newValue);
        return $newValue;
    }

    /**
     * Decrement the value of an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return int|bool
     */
    public function decrement($key, $value = 1)
    {
        $newValue = parent::decrement($key, $value);
        $this->putInMemoryCache($key, $newValue);
        return $newValue;
    }

    /**
     * Store an item in the cache indefinitely.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function forever($key, $value)
    {
        $this->putInMemoryCache($key, $value);
        parent::forever($key, $value);
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget($key)
    {
        unset($this->cache[$key]);
        return parent::forget($key);
    }


    /**
     * Remove all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        $this->flushInternalCache();
        parent::flush();
    }

    /**
     * Retrieve an item from the internal memory cache without trying the external driver.
     * Used in testing
     *
     * @param $key
     * @return mixed
     */
    public function getFromMemoryCache($key)
    {
        return $this->cache[$key] ?? null;
    }

    /**
     * Puts an item in the memory cache, but not in the external cache.
     * Used in testing
     *
     * @param $key
     * @param $value
     */
    public function putInMemoryCache($key, $value)
    {
        $this->cache[$key] = $value;
    }

    /**
     * Flushes the memory cache.
     * Calling this directly is generally only useful in testing.
     * Use flush() otherwise.
     */
    public function flushInternalCache()
    {
        $this->cache = [];
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->store->getPrefix();
    }
}
