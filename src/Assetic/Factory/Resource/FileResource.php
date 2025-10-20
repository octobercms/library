<?php namespace October\Rain\Assetic\Factory\Resource;

/**
 * FileResource is a resource is something formulae can be loaded from.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class FileResource implements ResourceInterface
{
    /**
     * @var string path
     */
    protected $path;

    /**
     * __construct
     *
     * @param string $path The path to a file
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * isFresh
     */
    public function isFresh($timestamp)
    {
        return file_exists($this->path) && filemtime($this->path) <= $timestamp;
    }

    /**
     * getContent
     */
    public function getContent()
    {
        return file_exists($this->path) ? file_get_contents($this->path) : '';
    }

    /**
     * __toString
     */
    public function __toString()
    {
        return $this->path;
    }
}
