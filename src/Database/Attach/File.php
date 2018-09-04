<?php namespace October\Rain\Database\Attach;

use Storage;
use File as FileHelper;
use October\Rain\Network\Http;
use October\Rain\Database\Model;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File as FileObj;
use Exception;

/**
 * File attachment model
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class File extends Model
{
    use \October\Rain\Database\Traits\Sortable;

    /**
     * @var string The table associated with the model.
     */
    protected $table = 'files';

    /**
     * Relations
     */
    public $morphTo = [
        'attachment' => []
    ];

    /**
     * @var array The attributes that aren't mass assignable.
     */
    protected $guarded = ['disk_name'];

    /**
     * @var array Known image extensions.
     */
    public static $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * @var array Hidden fields from array/json access
     */
    protected $hidden = ['attachment_type', 'attachment_id', 'is_public'];

    /**
     * @var array Add fields to array/json access
     */
    protected $appends = ['path', 'extension'];

    /**
     * @var mixed A local file name or an instance of an uploaded file,
     * objects of the \Symfony\Component\HttpFoundation\File\UploadedFile class.
     */
    public $data = null;

    /**
     * @var array Mime types
     */
    protected $autoMimeTypes = [
        'docx' => 'application/msword',
        'xlsx' => 'application/excel',
        'gif'  => 'image/gif',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'pdf'  => 'application/pdf'
    ];

    //
    // Constructors
    //

    /**
     * Creates a file object from a file an uploaded file.
     * @param Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile
     */
    public function fromPost($uploadedFile)
    {
        if ($uploadedFile === null) {
            return;
        }

        $this->file_name = $uploadedFile->getClientOriginalName();
        $this->file_size = $uploadedFile->getClientSize();
        $this->content_type = $uploadedFile->getMimeType();
        $this->disk_name = $this->getDiskName();

        /*
         * getRealPath() can be empty for some environments (IIS)
         */
        $realPath = empty(trim($uploadedFile->getRealPath()))
            ? $uploadedFile->getPath() . DIRECTORY_SEPARATOR . $uploadedFile->getFileName()
            : $uploadedFile->getRealPath();

        $this->putFile($realPath, $this->disk_name);

        return $this;
    }

    /**
     * Creates a file object from a file on the disk.
     */
    public function fromFile($filePath)
    {
        if ($filePath === null) {
            return;
        }

        $file = new FileObj($filePath);
        $this->file_name = $file->getFilename();
        $this->file_size = $file->getSize();
        $this->content_type = $file->getMimeType();
        $this->disk_name = $this->getDiskName();

        $this->putFile($file->getRealPath(), $this->disk_name);

        return $this;
    }
    
    /**
     * Creates a file object from raw data.
     *
     * @param $data string Raw data
     * @param $filename string Filename
     *
     * @return $this
     */
    public function fromData($data, $filename)
    {
        if ($data === null) {
            return;
        }

        $tempPath = temp_path($filename);
        FileHelper::put($tempPath, $data);

        $file = $this->fromFile($tempPath);
        FileHelper::delete($tempPath);

        return $file;
    }

    /**
     * Creates a file object from url
     * @param $url string URL
     * @param $filename string Filename
     * @return $this
     */
    public function fromUrl($url, $filename = null)
    {
        $data = Http::get($url);

        if ($data->code != 200) {
            throw new Exception(sprintf('Error getting file "%s", error code: %d', $data->url, $data->code));
        }

        if (empty($filename)) {
            $filename = FileHelper::basename($url);
        }

        return $this->fromData($data, $filename);
    }

    //
    // Attribute mutators
    //

    /**
     * Helper attribute for getPath.
     * @return string
     */
    public function getPathAttribute()
    {
        return $this->getPath();
    }

    /**
     * Helper attribute for getExtension.
     * @return string
     */
    public function getExtensionAttribute()
    {
        return $this->getExtension();
    }

    /**
     * Used only when filling attributes.
     * @return void
     */
    public function setDataAttribute($value)
    {
        $this->data = $value;
    }
    
    /**
     * Helper attribute for get image width.
     * @return string
     */
    public function getWidthAttribute()
    {
        if ($this->isImage()) {
            $dimensions = $this->getImageDimensions();
            
            return $dimensions[0];
        }
    }

    /**
     * Helper attribute for get image height.
     * @return string
     */
    public function getHeightAttribute()
    {
        if ($this->isImage()) {
            $dimensions = $this->getImageDimensions();
            
            return $dimensions[1];
        }
    }

    /**
     * Helper attribute for file size in human format.
     * @return string
     */
    public function getSizeAttribute()
    {
        return $this->sizeToString();
    }

    //
    // Raw output
    //

    /**
     * Outputs the raw file contents.
     * @return void
     */
    public function output($disposition = 'inline')
    {
        header("Content-type: ".$this->getContentType());
        header('Content-Disposition: '.$disposition.'; filename="'.$this->file_name.'"');
        header('Cache-Control: private');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: pre-check=0, post-check=0, max-age=0');
        header('Accept-Ranges: bytes');
        header('Content-Length: '.$this->file_size);
        echo $this->getContents();
    }

    /**
     * Outputs the raw thumbfile contents.
     * @return void
     */
    public function outputThumb($width, $height, $options = [])
    {
        $disposition = array_get($options, 'disposition', 'inline');
        $this->getThumb($width, $height, $options);
        $options = $this->getDefaultThumbOptions($options);
        $thumbFile = $this->getThumbFilename($width, $height, $options);
        $contents = $this->getContents($thumbFile);

        header("Content-type: ".$this->getContentType());
        header('Content-Disposition: '.$disposition.'; filename="'.basename($thumbFile).'"');
        header('Cache-Control: private');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: pre-check=0, post-check=0, max-age=0');
        header('Accept-Ranges: bytes');
        header('Content-Length: '.mb_strlen($contents, '8bit'));
        echo $contents;
    }

    //
    // Getters
    //

    /**
     * Returns the file name without path
     */
    public function getFilename()
    {
        return $this->file_name;
    }

    /**
     * Returns the file extension.
     */
    public function getExtension()
    {
        return FileHelper::extension($this->file_name);
    }

    /**
     * Returns the last modification date as a UNIX timestamp.
     * @return int
     */
    public function getLastModified($fileName = null)
    {
        if (!$fileName) {
            $fileName = $this->disk_name;
        }

        return $this->storageCmd('lastModified', $this->getStorageDirectory() . $this->getPartitionDirectory() . $fileName);
    }

    /**
     * Returns the file content type.
     */
    public function getContentType()
    {
        if ($this->content_type !== null) {
            return $this->content_type;
        }

        $ext = $this->getExtension();
        if (isset($this->autoMimeTypes[$ext])) {
            return $this->content_type = $this->autoMimeTypes[$ext];
        }

        return null;
    }

    /**
     * Get file contents from storage device.
     */
    public function getContents($fileName = null)
    {
        if (!$fileName) {
            $fileName = $this->disk_name;
        }

        return $this->storageCmd('get', $this->getStorageDirectory() . $this->getPartitionDirectory() . $fileName);
    }

    /**
     * Returns the public address to access the file.
     */
    public function getPath()
    {
        return $this->getPublicPath() . $this->getPartitionDirectory() . $this->disk_name;
    }

    /**
     * Returns a local path to this file. If the file is stored remotely,
     * it will be downloaded to a temporary directory.
     */
    public function getLocalPath()
    {
        if ($this->isLocalStorage()) {
            return $this->getLocalRootPath() . '/' . $this->getDiskPath();
        }

        $itemSignature = md5($this->getPath()) . $this->getLastModified();

        $cachePath = $this->getLocalTempPath($itemSignature . '.' . $this->getExtension());

        if (!FileHelper::exists($cachePath)) {
            $this->copyStorageToLocal($this->getDiskPath(), $cachePath);
        }

        return $cachePath;
    }

    /**
     * Returns the path to the file, relative to the storage disk.
     * @return string
     */
    public function getDiskPath()
    {
        return $this->getStorageDirectory() . $this->getPartitionDirectory() . $this->disk_name;
    }

    /**
     * Determines if the file is flagged "public" or not.
     */
    public function isPublic()
    {
        if (array_key_exists('is_public', $this->attributes)) {
            return $this->attributes['is_public'];
        }

        if (isset($this->is_public)) {
            return $this->is_public;
        }

        return true;
    }

    /**
     * Returns the file size as string.
     * @return string Returns the size as string.
     */
    public function sizeToString()
    {
        return FileHelper::sizeToString($this->file_size);
    }

    //
    // Events
    //

    /**
     * Before the model is saved
     * - check if new file data has been supplied, eg: $model->data = Input::file('something');
     */
    public function beforeSave()
    {
        /*
         * Process the data property
         */
        if ($this->data !== null) {
            if ($this->data instanceof UploadedFile) {
                $this->fromPost($this->data);
            }
            else {
                $this->fromFile($this->data);
            }

            $this->data = null;
        }
    }

    /**
     * After model is deleted
     * - clean up it's thumbnails
     */
    public function afterDelete()
    {
        try {
            $this->deleteThumbs();
            $this->deleteFile();
        }
        catch (Exception $ex) {}
    }

    //
    // Image handling
    //

    /**
     * Checks if the file extension is an image and returns true or false.
     */
    public function isImage()
    {
        return in_array(strtolower($this->getExtension()), static::$imageExtensions);
    }

    /**
     * Get image dimensions
     * @return array|bool
     */
    protected function getImageDimensions()
    {
        return getimagesize($this->getLocalPath());
    }

    /**
     * Generates and returns a thumbnail path.
     */
    public function getThumb($width, $height, $options = [])
    {
        if (!$this->isImage()) {
            return $this->getPath();
        }

        $width = (int) $width;
        $height = (int) $height;

        $options = $this->getDefaultThumbOptions($options);

        $thumbFile = $this->getThumbFilename($width, $height, $options);
        $thumbPath = $this->getStorageDirectory() . $this->getPartitionDirectory() . $thumbFile;
        $thumbPublic = $this->getPublicPath() . $this->getPartitionDirectory() . $thumbFile;

        if (!$this->hasFile($thumbFile)) {

            if ($this->isLocalStorage()) {
                $this->makeThumbLocal($thumbFile, $thumbPath, $width, $height, $options);
            }
            else {
                $this->makeThumbStorage($thumbFile, $thumbPath, $width, $height, $options);
            }

        }

        return $thumbPublic;
    }

    /**
     * Generates a thumbnail filename.
     * @return string
     */
    protected function getThumbFilename($width, $height, $options)
    {
        return 'thumb_' . $this->id . '_' . $width . '_' . $height . '_' . $options['offset'][0] . '_' . $options['offset'][1] . '_' . $options['mode'] . '.' . $options['extension'];
    }

    /**
     * Returns the default thumbnail options.
     * @return array
     */
    protected function getDefaultThumbOptions($overrideOptions = [])
    {
        $defaultOptions = [
            'mode'      => 'auto',
            'offset'    => [0, 0],
            'quality'   => 90,
            'sharpen'   => 0,
            'interlace' => false,
            'extension' => 'auto',
        ];

        if (!is_array($overrideOptions)) {
            $overrideOptions = ['mode' => $overrideOptions];
        }

        $options = array_merge($defaultOptions, $overrideOptions);

        $options['mode'] = strtolower($options['mode']);

        if (strtolower($options['extension']) == 'auto') {
            $options['extension'] = strtolower($this->getExtension());
        }

        return $options;
    }

    /**
     * Generate the thumbnail based on the local file system. This step is necessary
     * to simplify things and ensure the correct file permissions are given
     * to the local files.
     */
    protected function makeThumbLocal($thumbFile, $thumbPath, $width, $height, $options)
    {
        $rootPath = $this->getLocalRootPath();
        $filePath = $rootPath.'/'.$this->getDiskPath();
        $thumbPath = $rootPath.'/'.$thumbPath;

        /*
         * Handle a broken source image
         */
        if (!$this->hasFile($this->disk_name)) {
            BrokenImage::copyTo($thumbPath);
        }
        /*
         * Generate thumbnail
         */
        else {
            try {
                Resizer::open($filePath)
                    ->resize($width, $height, $options)
                    ->save($thumbPath)
                ;
            }
            catch (Exception $ex) {
                BrokenImage::copyTo($thumbPath);
            }
        }

        FileHelper::chmod($thumbPath);
    }

    /**
     * Generate the thumbnail based on a remote storage engine.
     */
    protected function makeThumbStorage($thumbFile, $thumbPath, $width, $height, $options)
    {
        $tempFile = $this->getLocalTempPath();
        $tempThumb = $this->getLocalTempPath($thumbFile);

        /*
         * Handle a broken source image
         */
        if (!$this->hasFile($this->disk_name)) {
            BrokenImage::copyTo($tempThumb);
        }
        /*
         * Generate thumbnail
         */
        else {
            $this->copyStorageToLocal($this->getDiskPath(), $tempFile);

            try {
                Resizer::open($tempFile)
                    ->resize($width, $height, $options)
                    ->save($tempThumb)
                ;
            }
            catch (Exception $ex) {
                BrokenImage::copyTo($tempThumb);
            }

            FileHelper::delete($tempFile);
        }

        /*
         * Publish to storage and clean up
         */
        $this->copyLocalToStorage($tempThumb, $thumbPath);
        FileHelper::delete($tempThumb);
    }

    /*
     * Delete all thumbnails for this file.
     */
    public function deleteThumbs()
    {
        $pattern = 'thumb_'.$this->id.'_';

        $directory = $this->getStorageDirectory() . $this->getPartitionDirectory();
        $allFiles = $this->storageCmd('files', $directory);
        $collection = [];
        foreach ($allFiles as $file) {
            if (starts_with(basename($file), $pattern)) {
                $collection[] = $file;
            }
        }

        /*
         * Delete the collection of files
         */
        if (!empty($collection)) {
            if ($this->isLocalStorage()) {
                FileHelper::delete($collection);
            }
            else {
                Storage::delete($collection);
            }
        }
    }

    //
    // File handling
    //

    /**
     * Generates a disk name from the supplied file name.
     */
    protected function getDiskName()
    {
        if ($this->disk_name !== null)
            return $this->disk_name;

        $ext = strtolower($this->getExtension());
        $name = str_replace('.', '', uniqid(null, true));

        return $this->disk_name = !empty($ext) ? $name.'.'.$ext : $name;
    }

    /**
     * Returns a temporary local path to work from.
     */
    protected function getLocalTempPath($path = null)
    {
        if (!$path) {
            return $this->getTempPath() . '/' . md5($this->getDiskPath()) . '.' . $this->getExtension();
        }

        return $this->getTempPath() . '/' . $path;
    }

    /**
     * Saves a file
     * @param string $sourcePath An absolute local path to a file name to read from.
     * @param string $destinationFileName A storage file name to save to.
     */
    protected function putFile($sourcePath, $destinationFileName = null)
    {
        if (!$destinationFileName) {
            $destinationFileName = $this->disk_name;
        }

        $destinationPath = $this->getStorageDirectory() . $this->getPartitionDirectory();

        if (!$this->isLocalStorage()) {
            return $this->copyLocalToStorage($sourcePath, $destinationPath . $destinationFileName);
        }

        /*
         * Using local storage, tack on the root path and work locally
         * this will ensure the correct permissions are used.
         */
        $destinationPath = $this->getLocalRootPath() . '/' . $destinationPath;

        /*
         * Verify the directory exists, if not try to create it. If creation fails
         * because the directory was created by a concurrent process then proceed,
         * otherwise trigger the error.
         */
        if (
            !FileHelper::isDirectory($destinationPath) &&
            !FileHelper::makeDirectory($destinationPath, 0777, true, true) &&
            !FileHelper::isDirectory($destinationPath)
        ) {
            trigger_error(error_get_last(), E_USER_WARNING);
        }

        return FileHelper::copy($sourcePath, $destinationPath . $destinationFileName);
    }

    /**
     * Delete file contents from storage device.
     * @return void
     */
    protected function deleteFile($fileName = null)
    {
        if (!$fileName) {
            $fileName = $this->disk_name;
        }

        $directory = $this->getStorageDirectory() . $this->getPartitionDirectory();
        $filePath = $directory . $fileName;

        if ($this->storageCmd('exists', $filePath)) {
            $this->storageCmd('delete', $filePath);
        }

        $this->deleteEmptyDirectory($directory);
    }

    /**
     * Check file exists on storage device.
     * @return void
     */
    protected function hasFile($fileName = null)
    {
        $filePath = $this->getStorageDirectory() . $this->getPartitionDirectory() . $fileName;
        return $this->storageCmd('exists', $filePath);
    }

    /**
     * Checks if directory is empty then deletes it,
     * three levels up to match the partition directory.
     * @return void
     */
    protected function deleteEmptyDirectory($dir = null)
    {
        if (!$this->isDirectoryEmpty($dir)) {
            return;
        }

        $this->storageCmd('deleteDirectory', $dir);

        $dir = dirname($dir);
        if (!$this->isDirectoryEmpty($dir)) {
            return;
        }

        $this->storageCmd('deleteDirectory', $dir);

        $dir = dirname($dir);
        if (!$this->isDirectoryEmpty($dir)) {
            return;
        }

        $this->storageCmd('deleteDirectory', $dir);
    }

    /**
     * Returns true if a directory contains no files.
     * @return void
     */
    protected function isDirectoryEmpty($dir)
    {
        if (!$dir) {
            return null;
        }

        return count($this->storageCmd('allFiles', $dir)) === 0;
    }

    //
    // Storage interface
    //

    /**
     * Calls a method against File or Storage depending on local storage.
     * This allows local storage outside the storage/app folder and is
     * also good for performance. For local storage, *every* argument
     * is prefixed with the local root path. Props to Laravel for
     * the unified interface.
     * @return mixed
     */
    protected function storageCmd()
    {
        $args = func_get_args();
        $command = array_shift($args);

        if ($this->isLocalStorage()) {
            $interface = 'File';
            $path = $this->getLocalRootPath();
            $args = array_map(function($value) use ($path) {
                return $path . '/' . $value;
            }, $args);
        }
        else {
            $interface = 'Storage';
        }

        return forward_static_call_array([$interface, $command], $args);
    }

    /**
     * Copy the Storage to local file
     */
    protected function copyStorageToLocal($storagePath, $localPath)
    {
        return FileHelper::put($localPath, Storage::get($storagePath));
    }

    /**
     * Copy the local file to Storage
     */
    protected function copyLocalToStorage($localPath, $storagePath)
    {
        return Storage::put($storagePath, FileHelper::get($localPath), $this->isPublic() ? 'public' : null);
    }

    //
    // Configuration
    //

    /**
     * Returns the maximum size of an uploaded file as configured in php.ini
     * @return int The maximum size of an uploaded file in kilobytes
     */
    public static function getMaxFilesize()
    {
        return round(UploadedFile::getMaxFilesize() / 1024);
    }

    /**
     * Define the internal storage path, override this method to define.
     */
    public function getStorageDirectory()
    {
        if ($this->isPublic()) {
            return 'uploads/public/';
        }

        return 'uploads/protected/';
    }

    /**
     * Define the public address for the storage path.
     */
    public function getPublicPath()
    {
        if ($this->isPublic()) {
            return 'http://localhost/uploads/public/';
        }

        return 'http://localhost/uploads/protected/';
    }

    /**
     * Define the internal working path, override this method to define.
     */
    public function getTempPath()
    {
        $path = temp_path() . '/uploads';

        if (!FileHelper::isDirectory($path)) {
            FileHelper::makeDirectory($path, 0777, true, true);
        }

        return $path;
    }

    /**
     * Returns true if the storage engine is local.
     * @return bool
     */
    protected function isLocalStorage()
    {
        return Storage::getDefaultDriver() == 'local';
    }

    /**
    * Generates a partition for the file.
    * return /ABC/DE1/234 for an name of ABCDE1234.
    * @param Attachment $attachment
    * @param string $styleName
    * @return mixed
    */
    protected function getPartitionDirectory()
    {
        return implode('/', array_slice(str_split($this->disk_name, 3), 0, 3)) . '/';
    }

    /**
     * If working with local storage, determine the absolute local path.
     * @return string
     */
    protected function getLocalRootPath()
    {
        return storage_path().'/app';
    }
}
