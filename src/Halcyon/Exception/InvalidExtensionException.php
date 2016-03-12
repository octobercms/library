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
     * A list of allowable extensions.
     *
     * @var array
     */
    protected $allowedExtensions;

    /**
     * Set the affected file extension.
     *
     * @param  string   $invalidExtension
     * @return $this
     */
    public function setInvalidExtension($invalidExtension)
    {
        $this->invalidExtension = $invalidExtension;

        $this->message = "The specified file extension [{$invalidExtension}] is invalid.";

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

    /**
     * Set the list of allowed extensions.
     *
     * @param  array   $allowedExtensions
     * @return $this
     */
    public function setAllowedExtensions(array $allowedExtensions)
    {
        $this->allowedExtensions = $allowedExtensions;

        return $this;
    }

    /**
     * Get the list of allowed extensions.
     *
     * @return string
     */
    public function getAllowedExtensions()
    {
        return $this->allowedExtensions;
    }
}
