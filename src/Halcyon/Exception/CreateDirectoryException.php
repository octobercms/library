<?php namespace October\Rain\Halcyon\Exception;

use RuntimeException;

/**
 * CreateDirectoryException
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class CreateDirectoryException extends RuntimeException
{
    /**
     * @var string invalidPath of the affected directory path
     */
    protected $invalidPath;

    /**
     * setInvalidPath sets the affected directory path
     */
    public function setInvalidPath(string $path): CreateDirectoryException
    {
        $this->invalidPath = $path;

        $this->message = "Error creating directory [{$path}]. Please check write permissions.";

        return $this;
    }

    /**
     * getInvalidPath is the affected directory path
     */
    public function getInvalidPath(): string
    {
        return $this->invalidPath;
    }
}
