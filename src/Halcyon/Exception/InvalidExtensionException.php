<?php namespace October\Rain\Halcyon\Exception;

use RuntimeException;

class InvalidExtensionException extends RuntimeException
{
    /**
     * Name of the affected file extension.
     *
     * @var string
     */
    protected $invalidExtension;

    /**
     * Set the affected file extension.
     *
     * @param  string   $invalidExtension
     * @return $this
     */
    public function setInvalidExtension($invalidExtension)
    {
        $this->invalidExtension = $invalidExtension;

        $this->message = "No file name attribute (invalidExtension) specified for name [{$invalidExtension}].";

        return $this;
    }

    /**
     * Get the affected file extension.
     *
     * @return string
     */
    public function getInvalidExtension()
    {
        return $this->invalidExtension;
    }
}
