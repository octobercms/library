<?php namespace October\Rain\Storage;

/**
 * File class
 *
 * This class is generic object representation of a file used by
 * the storage drivers.
 */

class File
{

    public $name;

    public $extension;

    public $size;

    public $contents;

    public $content_type;

    /**
     * @var Storage driver
     */
    protected $driver;

    /**
     * Constructor method
     * @param October\Rain\Attach\Attachment $attachedFile
     */
    function __construct($fileName, $driver)
    {
        $this->name = $fileName;
        $this->driver = $driver;
    }

}