<?php namespace October\Rain\Attach\Storage;

use October\Rain\Attach\Exceptions;
use October\Rain\Attach\File\UploadedFile;
use Config;

/**
 * File System Storage driver
 *
 * Expected options:
 * - path: Absolute path to directory
 */
class Filesystem implements StorageInterface
{
    public $options;

    /**
     * Constructor method
     * @param October\Rain\Attach\Attachment $attachedFile
     */
    function __construct($options)
    {
        $this->validateOptions($options);
        $this->options = $options;
    }

    /**
     * Return the url for a file upload.
     * @param  string $styleName 
     * @return string
     */
    public function url($styleName)
    {
        return $this->attachedFile->getInterpolator()->interpolate($this->attachedFile->url, $this->attachedFile, $styleName);
    }

    /**
     * Return the path (on disk) of a file upload.
     * @param  string $styleName 
     * @return string
     */
    public function path($styleName)
    {
        return $this->attachedFile->getInterpolator()->interpolate($this->attachedFile->path, $this->attachedFile, $styleName);
    }

    /**
     * Remove an attached file.
     * @param array $filePaths
     * @return void
     */
    public function remove($filePaths)
    {
        foreach ($filePaths as $filePath) {
            $directory = dirname($filePath);
            $this->emptyDirectory($directory, true);
        }
    }

    /**
     * Move an uploaded file to it's intended destination.
     * The file can be an actual uploaded file object or the path to
     * a resized image file on disk.
     * @param UploadedFile $file 
     * @param string $filePath
     * @return void 
     */
    public function move($file, $filePath)
    {
        $this->buildDirectory($filePath);
        $this->moveFile($file, $filePath);
        $this->setPermissions($filePath, $this->attachedFile->override_file_permissions);
    }

    /**
     * Determine if a style directory needs to be built and if so create it.
     * @param  string $filePath
     * @return void
     */
    protected function buildDirectory($filePath)
    {
        $directory = dirname($filePath);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    /**
     * Set the file permissions of a file upload
     * Does not ignore umask.
     * @param string $filePath
     * @param integer $overrideFilePermissions
     */
    protected function setPermissions($filePath, $overrideFilePermissions)
    {
        if ($overrideFilePermissions) {
            chmod($filePath, $overrideFilePermissions & ~umask());
        }
        elseif (is_null($overrideFilePermissions)) {
            chmod($filePath, 0666 & ~umask());
        }
    }

    /**
     * Attempt to move and uploaded file to it's intended location on disk.
     * @param  string $file
     * @param  string $filePath
     * @return void
     */
    protected function moveFile($file, $filePath)
    {
        if (!rename($file, $filePath)) {
            $error = error_get_last();
            throw new Exceptions\FileException(sprintf('Could not move the file "%s" to "%s" (%s)', $file, $filePath, strip_tags($error['message'])));
        }
    }

    /**
     * Recursively delete the files in a directory.
     * @desc Recursively loops through each file in the directory and deletes it.
     * @param string $directory
     * @param boolean $deleteDirectory
     * @return void
     */
    protected function emptyDirectory($directory, $deleteDirectory = false)
    {
        if (!is_dir($directory) || !($directoryHandle = opendir($directory))) {
            return;
        }
        
        while (false !== ($object = readdir($directoryHandle))) 
        {
            if ($object == '.' || $object == '..') {
                continue;
            }

            if (!is_dir($directory.'/'.$object)) {
                unlink($directory.'/'.$object);
            }
            else {
                // The object is a folder, recurse through it
                $this->emptyDirectory($directory.'/'.$object, true);
            }
        }
        
        if ($deleteDirectory) {
            closedir($directoryHandle);
            rmdir($directory);
        }
    }

    /**
     * Validate the attachment optioins for an attachment type when the storage
     * driver is set to 'filesystem'.
     * @param  array $options 
     * @return void
     */
    private function validateOptions($options)
    {
        if (preg_match("/:id\b/", $options['url']) !== 1 && preg_match("/:id_partition\b/", $options['url']) !== 1 && preg_match("/:hash\b/", $options['url']) !== 1) {
            throw new \Exception('Invalid Url: an id, id_partition, or hash interpolation is required.', 1);
        }
    }
}