<?php namespace October\Rain\Halcyon\Exception;

use RuntimeException;

class InvalidFileNameException extends RuntimeException
{
    /**
     * Name of the affected file name.
     *
     * @var string
     */
    protected $invalidFileName;

    /**
     * Set the affected file name.
     *
     * @param  string   $invalidFileName
     * @return $this
     */
    public function setInvalidFileName($invalidFileName)
    {
        $this->invalidFileName = $invalidFileName;

        $this->message = "The specified file name [{$invalidFileName}] is invalid.";

        return $this;
    }

    /**
     * Get the affected file name.
     *
     * @return string
     */
    public function getInvalidFileName()
    {
        return $this->invalidFileName;
    }
}
