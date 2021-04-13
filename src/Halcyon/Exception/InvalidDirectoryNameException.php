<?php namespace October\Rain\Halcyon\Exception;

use RuntimeException;

/**
 * InvalidDirectoryNameException
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class InvalidDirectoryNameException extends RuntimeException
{
    /**
     * @var string invalidDirName of the affected file name
     */
    protected $invalidDirName;

    /**
     * setInvalidDirectoryName sets the affected file name
     */
    public function setInvalidDirectoryName(string $invalidDirName): InvalidDirectoryNameException
    {
        $this->invalidDirName = $invalidDirName;

        $this->message = "The specified directory name [{$invalidDirName}] is invalid.";

        return $this;
    }

    /**
     * getInvalidDirectoryName gets the affected file name
     */
    public function getInvalidDirectoryName(): string
    {
        return $this->invalidDirName;
    }
}
