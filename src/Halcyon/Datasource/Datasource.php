<?php namespace October\Rain\Halcyon\Datasource;

/**
 * Datasource base class.
 */
class Datasource
{

    /**
     * The query post processor implementation.
     *
     * @var \October\Rain\Halcyon\Processors\Processor
     */
    protected $postProcessor;

    /**
     * Get the query post processor used by the connection.
     *
     * @return \October\Rain\Halcyon\Processors\Processor
     */
    public function getPostProcessor()
    {
        return $this->postProcessor;
    }

    /**
     * Generate a cache key unique to this datasource.
     */
    public function makeCacheKey($name = '')
    {
        return crc32($name);
    }

}