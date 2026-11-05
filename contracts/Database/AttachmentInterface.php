<?php namespace October\Contracts\Database;

/**
 * AttachmentInterface is a file model that stores an uploaded file and returns its addresses.
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface AttachmentInterface
{
    /**
     * fromPost fills the model from an uploaded file.
     */
    public function fromPost($fileInput);

    /**
     * getPath returns the public address of the file.
     */
    public function getPath($fileName = null);

    /**
     * getThumb returns the address of a thumbnail at the given size.
     */
    public function getThumb($width, $height, $options = []);

    /**
     * getMaxFilesize returns the largest file size accepted, in kilobytes.
     */
    public static function getMaxFilesize();
}
