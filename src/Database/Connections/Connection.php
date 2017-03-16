<?php namespace October\Rain\Database\Connections;

use October\Rain\Database\MemoryCache;
use October\Rain\Database\QueryBuilder;
use Illuminate\Database\Connection as ConnectionBase;

class Connection extends ConnectionBase
{
    /**
     * Get a new query builder instance.
     *
     * @return \October\Rain\Database\QueryBuilder
     */
    public function query()
    {
        return new QueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    /**
     * Flush the memory cache.
     * @return void
     */
    public static function flushDuplicateCache()
    {
        MemoryCache::instance()->flush();
    }
}
