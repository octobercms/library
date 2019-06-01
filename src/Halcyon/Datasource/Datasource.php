<?php namespace October\Rain\Halcyon\Datasource;

/**
 * Datasource base class.
 */
class Datasource
{
    use \October\Rain\Support\Traits\Emitter;

    /**
     * @var bool Indicates if the record is currently being force deleted.
     */
    protected $forceDeleting = false;

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
     * Force the deletion of a record against the datasource
     *
     * @param  string  $dirName
     * @param  string  $fileName
     * @param  string  $extension
     * @return void
     */
    public function forceDelete(string $dirName, string $fileName, string $extension)
    {
        $this->forceDeleting = true;

        $this->delete($dirName, $fileName, $extension);

        $this->forceDeleting = false;
    }

    /**
     * Generate a cache key unique to this datasource.
     */
    public function makeCacheKey($name = '')
    {
        return crc32($name);
    }

}