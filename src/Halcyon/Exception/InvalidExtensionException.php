<?php namespace October\Rain\Halcyon\Exception;

use RuntimeException;

/**
 * InvalidExtensionException
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class InvalidExtensionException extends RuntimeException
{
    /**
     * @var string invalidExtension
     */
    protected $invalidExtension;

    /**
     * @var array allowedExtensions
     */
    protected $allowedExtensions;

    /**
     * setInvalidExtension sets the affected file extension
     */
    public function setInvalidExtension(string $invalidExtension): InvalidExtensionException
    {
        $this->invalidExtension = $invalidExtension;

        $this->message = "The specified file extension [{$invalidExtension}] is invalid.";

        return $this;
    }

    /**
     * getInvalidExtension gets the affected file extension
     */
    public function getInvalidExtension(): string
    {
        return $this->invalidExtension;
    }

    /**
     * setAllowedExtensions sets the list of allowed extensions
     */
    public function setAllowedExtensions(array $allowedExtensions): InvalidExtensionException
    {
        $this->allowedExtensions = $allowedExtensions;

        return $this;
    }

    /**
     * getAllowedExtensions gets the list of allowed extensions
     */
    public function getAllowedExtensions(): array
    {
        return $this->allowedExtensions;
    }
}
