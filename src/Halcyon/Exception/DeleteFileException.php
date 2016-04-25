<?php namespace October\Rain\Halcyon\Exception;

use RuntimeException;

class DeleteFileException extends RuntimeException
{
    /**
     * Name of the affected file path.
     *
     * @var string
     */
    protected $invalidPath;

    /**
     * Set the affected file path.
     *
     * @param  string   $model
     * @return $this
     */
    public function setInvalidPath($path)
    {
        $this->invalidPath = $path;

        $this->message = "Error deleting file [{$path}]. Please check write permissions.";

        return $this;
    }

    /**
     * Get the affected file path.
     *
     * @return string
     */
    public function getInvalidPath()
    {
        return $this->invalidPath;
    }
}
