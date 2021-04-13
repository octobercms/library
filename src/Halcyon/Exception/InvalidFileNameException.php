<?php namespace October\Rain\Halcyon\Exception;

use RuntimeException;

/**
 * InvalidFileNameException
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class InvalidFileNameException extends RuntimeException
{
    /**
     * @var string invalidFileName
     */
    protected $invalidFileName;

    /**
     * setInvalidFileName the affected file name
     */
    public function setInvalidFileName(string $invalidFileName): InvalidFileNameException
    {
        $this->invalidFileName = $invalidFileName;

        $this->message = "The specified file name [{$invalidFileName}] is invalid.";

        return $this;
    }

    /**
     * getInvalidFileName gets the affected file name
     */
    public function getInvalidFileName(): string
    {
        return $this->invalidFileName;
    }
}
