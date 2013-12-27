<?php namespace October\Rain\Attach\Storage;

use October\Rain\Attach\File\UploadedFile;
use Aws\S3\S3Client;
use Config;

/**
 * Amazon S3 Storage driver
 *
 * Expected options:
 * - path: Unique key where the file will be stored.
 * - key: Public key
 * - secret: Secret phrase
 * - bucket: Storage bucket
 * - acl: This is a string/array that should be one of the canned access policies that S3 provides (private, public-read, public-read-write, authenticated-read, bucket-owner-read, bucket-owner-full-control).
 * - scheme: Protocol (http, https).
 * - region: Reigon name for the bucket.
 */
class S3 implements StorageInterface
{
    public $options;

    /**
     * An AWS S3Client instance.
     * @var S3Client
     */
    protected $s3Client;

    /**
     * Boolean flag indicating if this attachment's bucket currently exists.
     * @var array
     */
    protected $bucketExists = false;

    /**
     * Constructor method
     * @param October\Rain\Attach\Attachment $attachedFile
     */
    function __construct($options)
    {
        $this->validateOptions($options);
        $this->options = $options;
        $this->s3Client = S3Client::factory([
            'key' => $attachedFile->key,
            'secret' => $attachedFile->secret,
            'region' => $attachedFile->region,
            'scheme' => $attachedFile->scheme
        ]);
    }

    /**
     * Return the url for a file upload.
     * @param string $styleName 
     * @return string
     */
    public function url($styleName)
    {
        return $this->s3Client->getObjectUrl($this->getBucket(), $this->path($styleName));
    }

    /**
     * Return the key the uploaded file object is stored under within a bucket.
     * @param string $styleName 
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
        if ($filePaths) {
            $this->s3Client->deleteObjects(['Bucket' => $this->getBucket(), 'Objects' => $this->getKeys($filePaths)]);
        }
    }

    /**
     * Move an uploaded file to it's intended destination.
     * The file can be an actual uploaded file object or the path to
     * a resized image file on disk.
     * @param  UploadedFile $file 
     * @param  string $filePath
     * @return void 
     */
    public function move($file, $filePath)
    {
        $this->s3Client->putObject(['Bucket' => $this->getBucket(), 'Key' => $filePath, 'SourceFile' => $file, 'ACL' => $this->attachedFile->ACL]);
    }

    /**
     * Return an array of paths (bucket keys) for an attachment.
     * There will be one path for each of the attachmetn's styles.
     * @param  $filePaths
     * @return array
     */
    protected function getKeys($filePaths)
    {
        $keys = [];

        foreach ($filePaths as $filePath) {
            $keys[] = ['Key' => $filePath];
        }

        return $keys;
    }

    /**
     * This is a wrapper method for returning the name of an attachment's bucket.
     * If the bucket doesn't exist we'll build it first before returning it's name.
     * @return string
     */
    protected function getBucket()
    {
        $bucketName = $this->attachedFile->bucket;
        if (!$this->bucketExists) {
            $this->buildBucket($bucketName);
        }

        return $bucketName;
    }

    /**
     * Attempt to build a bucket (if it doesn't already exist).
     * @param  string $bucketName
     * @return void
     */
    public function buildBucket($bucketName)
    {
        if (!$this->s3Client->doesBucketExist($bucketName, true)) {
            $this->s3Client->createBucket(['ACL' => $this->attachedFile->ACL, 'Bucket' => $bucketName, 'LocationConstraint' => $this->attachedFile->region]);
        }

        $this->bucketExists = true;
    }

    /**
     * Validate the attachment optioins for an attachment type when the storage
     * driver is set to 's3'.
     * @param  array $options 
     * @return void
     */
    private function validateOptions($options)
    {
        if (!$options['bucket']) {
            throw new \Exception('Invalid Path: a bucket interpolation is required for s3 storage.');
        }
    }
}