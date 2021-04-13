<?php namespace October\Rain\Halcyon\Exception;

use RuntimeException;

/**
 * FileExistsException
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class FileExistsException extends RuntimeException
{
    /**
     * @var string invalidPath of the affected directory path
     */
    protected $invalidPath;

    /**
     * setInvalidPath sets the affected directory path
     */
    public function setInvalidPath(string $path): FileExistsException
    {
        $this->invalidPath = $path;

        $this->message = "A file already exists at [{$path}].";

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
