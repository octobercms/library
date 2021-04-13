<?php namespace October\Rain\Halcyon\Exception;

use RuntimeException;

/**
 * DeleteFileException
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class DeleteFileException extends RuntimeException
{
    /**
     * @var string invalidPath of the affected directory path
     */
    protected $invalidPath;

    /**
     * setInvalidPath sets the affected directory path
     */
    public function setInvalidPath(string $path): DeleteFileException
    {
        $this->invalidPath = $path;

        $this->message = "Error deleting file [{$path}]. Please check write permissions.";

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
