<?php namespace October\Rain\Halcyon\Datasource;

use October\Rain\Halcyon\Processors\Processor;

/**
 * Datasource base class
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class Datasource
{
    use \October\Rain\Support\Traits\Emitter;

    /**
     * @var bool forceDeleting indicates if the record is currently being force deleted
     */
    protected $forceDeleting = false;

    /**
     * @var \October\Rain\Halcyon\Processors\Processor
     */
    protected $postProcessor;

    /**
     * getPostProcessor used by the connection
     */
    public function getPostProcessor(): Processor
    {
        return $this->postProcessor;
    }

    /**
     * delete against the datasource
     */
    public function delete(string $dirName, string $fileName, string $extension): bool
    {
        return true;
    }

    /**
     * forceDelete a record against the datasource
     */
    public function forceDelete(string $dirName, string $fileName, string $extension): bool
    {
        $this->forceDeleting = true;

        $result = $this->delete($dirName, $fileName, $extension);

        $this->forceDeleting = false;

        return $result;
    }

    /**
     * makeCacheKey unique to this datasource
     */
    public function makeCacheKey(string $name = ''): string
    {
        return (string) crc32($name);
    }
}
