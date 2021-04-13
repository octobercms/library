<?php namespace October\Rain\Database;

use App;
use Illuminate\Database\Query\Builder as QueryBuilderBase;

/**
 * QueryBuilder restores some features that were removed from base, it also
 * adds some new ones
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class QueryBuilder extends QueryBuilderBase
{
    /**
     * @var string cacheKey is the key that should be used when caching the query
     */
    protected $cacheKey;

    /**
     * @var int cacheMinutes is the number of minutes to cache the query
     */
    protected $cacheMinutes;

    /**
     * @var array cacheTags is the tags for the query cache
     */
    protected $cacheTags;

    /**
     * @var bool cachingDuplicateQueries indicates whether duplicate queries are being cached in memory
     */
    protected $cachingDuplicateQueries = false;

    /**
     * Get an array with the values of a given column.
     *
     * @param  string  $column
     * @param  string|null  $key
     * @return array
     */
    public function lists($column, $key = null)
    {
        return $this->pluck($column, $key)->all();
    }

    /**
     * Indicate that the query results should be cached.
     *
     * @param  \DateTime|int  $minutes
     * @param  string  $key
     * @return $this
     */
    public function remember($minutes, $key = null)
    {
        $this->cacheMinutes = $minutes;
        $this->cacheKey = $key;

        return $this;
    }

    /**
     * Indicate that the query results should be cached forever.
     *
     * @param  string  $key
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function rememberForever($key = null)
    {
        return $this->remember(-1, $key);
    }

    /**
     * Indicate that the results, if cached, should use the given cache tags.
     *
     * @param  array|mixed  $cacheTags
     * @return $this
     */
    public function cacheTags($cacheTags)
    {
        $this->cacheTags = $cacheTags;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get($columns = ['*'])
    {
        if ($this->cachingDuplicates()) {
            return $this->getDuplicateCached((array) $columns);
        }

        if (!is_null($this->cacheMinutes)) {
            return $this->getCached((array) $columns);
        }

        return parent::get($columns);
    }

    /**
     * getDuplicateCached checks the memory cache before executing the query
     */
    protected function getDuplicateCached(array $columns = ['*'])
    {
        if (is_null($this->columns)) {
            $this->columns = $columns;
        }

        $cache = MemoryCache::instance();

        if ($cache->has($this)) {
            $results = collect($cache->get($this));
        }
        else {
            $results = !is_null($this->cacheMinutes)
                ? $this->getCached($columns)
                : parent::get($columns);

            $cache->put($this, $results->all());
        }

        return $results;
    }

    /**
     * getCached executes the query as a cached "select" statement.
     */
    public function getCached(array $columns = ['*'])
    {
        if (is_null($this->columns)) {
            $this->columns = $columns;
        }

        // If the query is requested to be cached, we will cache it using a unique key
        // for this database connection and query statement, including the bindings
        // that are used on this query, providing great convenience when caching.
        list($key, $minutes) = $this->getCacheInfo();

        $cache = $this->getCache();

        $callback = $this->getCacheCallback($columns);

        // If the "minutes" value is less than zero, we will use that as the indicator
        // that the value should be remembered values should be stored indefinitely
        // and if we have minutes we will use the typical remember function here.
        if ($minutes < 0) {
            $results = $cache->rememberForever($key, $callback);
        }
        else {
            $expiresAt = now()->addMinutes($minutes);
            $results = $cache->remember($key, $expiresAt, $callback);
        }

        return collect($results);
    }

    /**
     * Get the cache object with tags assigned, if applicable.
     *
     * @return \Illuminate\Cache\CacheManager
     */
    protected function getCache()
    {
        $cache = App::make('cache');

        return $this->cacheTags ? $cache->tags($this->cacheTags) : $cache;
    }

    /**
     * getCacheInfo returns key and cache minutes
     */
    protected function getCacheInfo(): array
    {
        return [$this->getCacheKey(), $this->cacheMinutes];
    }

    /**
     * getCacheKey returns a unique cache key for the complete query
     */
    public function getCacheKey(): string
    {
        return $this->cacheKey ?: $this->generateCacheKey();
    }

    /**
     * Generate the unique cache key for the query.
     *
     * @return string
     */
    public function generateCacheKey()
    {
        $name = $this->connection->getName();

        return md5($name.$this->toSql().serialize($this->getBindings()));
    }

    /**
     * Get the Closure callback used when caching queries.
     *
     * @param  array  $columns
     * @return \Closure
     */
    protected function getCacheCallback($columns)
    {
        return function () use ($columns) {
            return parent::get($columns)->all();
        };
    }

    /**
     * Retrieve the "count" result of the query,
     * also strips off any orderBy clause.
     *
     * @param  string  $columns
     * @return int
     */
    public function count($columns = '*')
    {
        $previousOrders = $this->orders;

        $this->orders = null;

        $result = parent::count($columns);

        $this->orders = $previousOrders;

        return $result;
    }

    /**
     * Update a record in the database.
     *
     * @param  array $values
     * @return int
     */
    public function update(array $values)
    {
        $this->clearDuplicateCache();

        return parent::update($values);
    }

    /**
     * Delete a record from the database.
     *
     * @param  mixed $id
     * @return int
     */
    public function delete($id = null)
    {
        $this->clearDuplicateCache();

        return parent::delete($id);
    }

    /**
     * Insert a new record and get the value of the primary key.
     *
     * @param  array   $values
     * @param  string  $sequence
     * @return int
     */
    public function insertGetId(array $values, $sequence = null)
    {
        $this->clearDuplicateCache();

        return parent::insertGetId($values, $sequence);
    }

    /**
     * Insert a new record into the database.
     *
     * @param  array  $values
     * @return bool
     */
    public function insert(array $values)
    {
        $this->clearDuplicateCache();

        return parent::insert($values);
    }

    /**
     * Run a truncate statement on the table.
     *
     * @return void
     */
    public function truncate()
    {
        $this->clearDuplicateCache();

        parent::truncate();
    }

    /**
     * Clear memory cache for the given table.
     *
     * @param  string|null  $table
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function clearDuplicateCache($table = null)
    {
        MemoryCache::instance()->forget($table ?: $this->from);

        return $this;
    }

    /**
     * Flush the memory cache.
     *
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function flushDuplicateCache()
    {
        MemoryCache::instance()->flush();

        return $this;
    }

    /**
     * Enable the memory cache on the query.
     *
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function enableDuplicateCache()
    {
        $this->cachingDuplicateQueries = true;

        return $this;
    }

    /**
     * Disable the memory cache on the query.
     *
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function disableDuplicateCache()
    {
        $this->cachingDuplicateQueries = false;

        return $this;
    }

    /**
     * Determine whether we're caching duplicate queries.
     *
     * @return bool
     */
    public function cachingDuplicates()
    {
        return $this->cachingDuplicateQueries;
    }
}
