<?php namespace October\Rain\Halcyon\Theme;

/**
 * File based theme.
 */
class Theme
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
     * Generate a cache key unique to this theme.
     */
    public function makeCacheKey($name)
    {
        return crc32($name);
    }

}