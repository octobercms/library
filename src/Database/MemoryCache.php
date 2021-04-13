<?php namespace October\Rain\Database;

/**
 * MemoryCache stores query results in memory to avoid running duplicate queries
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class MemoryCache
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * @var array cache results
     */
    protected $cache = [];

    /**
     * @var array tableMap between hashed keys and table names
     */
    protected $tableMap = [];

    /**
     * @var bool enabled store enabled state
     */
    protected $enabled = true;

    /**
     * enabled checks if the memory cache is enabled
     * @return bool
     */
    public function enabled($switch = null)
    {
        if ($switch !== null) {
            $this->enabled = $switch;
        }

        return $this->enabled;
    }

    /**
     * has checks if the given query is cached
     *
     * @param  QueryBuilder  $query
     * @return bool
     */
    public function has(QueryBuilder $query)
    {
        return $this->enabled && isset($this->cache[$this->hash($query)]);
    }

    /**
     * get the cached results for the given query.
     *
     * @param  QueryBuilder  $query
     * @return array|null
     */
    public function get(QueryBuilder $query)
    {
        if ($this->has($query)) {
            return $this->cache[$this->hash($query)];
        }

        return null;
    }

    /**
     * put stores the results for the given query
     *
     * @param  QueryBuilder  $query
     * @param  array  $results
     * @return void
     */
    public function put(QueryBuilder $query, array $results)
    {
        if (!$this->enabled) {
            return;
        }

        $hash = $this->hash($query);

        $this->cache[$hash] = $results;

        $this->tableMap[(string) $query->from][] = $hash;
    }

    /**
     * forget deletes the cache for the given table
     *
     * @param $table
     */
    public function forget($table)
    {
        if (!isset($this->tableMap[$table])) {
            return;
        }

        foreach ($this->tableMap[$table] as $hash) {
            unset($this->cache[$hash]);
        }

        unset($this->tableMap[$table]);
    }

    /**
     * flush clears the memory cache
     */
    public function flush()
    {
        $this->cache = [];
        $this->tableMap = [];
    }

    /**
     * hash calculates a hash key for the given query
     *
     * @param  QueryBuilder  $query
     * @return string
     */
    protected function hash(QueryBuilder $query)
    {
        // First we will cast all bindings to string, so we can ensure the same
        // hash format regardless of the binding type provided by the user.
        $bindings = array_map(function ($binding) {
            return (string) $binding;
        }, $query->getBindings());

        $name = $query->getConnection()->getName();

        return md5($name . $query->toSql() . serialize($bindings));
    }
}
