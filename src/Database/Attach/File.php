<?php namespace October\Rain\Database\Attach;

use Log;
use Cache;
use Storage;
use Response;
use File as FileHelper;
use October\Rain\Network\Http;
use October\Rain\Database\Model;
use October\Rain\Resize\Resizer;
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
     * @var string table associated with the model
     */
    protected $table = 'files';

    /**
     * morphTo relation
     */
    public $morphTo = [
        'attachment' => []
    ];

    /**
     * @var array fillable attributes are mass assignable
     */
    protected $fillable = [
        'file_name',
        'title',
        'description',
        'field',
        'attachment_id',
        'attachment_type',
        'is_public',
        'sort_order',
        'data',
    ];

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = [];

    /**
     * @var array imageExtensions known
     */
    public static $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * @var array hidden fields from array/json access
     */
    protected $hidden = ['attachment_type', 'attachment_id', 'is_public'];

    /**
     * @var array appends fields to array/json access
     */
    protected $appends = ['path', 'extension'];

    /**
     * @var mixed data is a local file name or an instance of an uploaded file,
     * objects of the \Symfony\Component\HttpFoundation\File\UploadedFile class.
     */
    public $data = null;

    /**
     * @var array autoMimeTypes
     */
    protected $autoMimeTypes = [
        'docx' => 'application/msword',
        'xlsx' => 'application/excel',
        'gif'  => 'image/gif',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'pdf'  => 'application/pdf',
        'svg'  => 'image/svg+xml',
    ];

    //
    // Constructors
    //

    /**
     * fromPost creates a file object from a file an uploaded file
     * @param Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile
     */
    public function fromPost($uploadedFile)
    {
        if ($uploadedFile === null) {
            return;
        }

        $this->file_name = $uploadedFile->getClientOriginalName();
        $this->file_size = $uploadedFile->getSize();
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
     * fromFile creates a file object from a file on the disk
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
     * fromData creates a file object from raw data
     * @param $data string Raw data
     * @param $filename string Filename
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
     * fromUrl creates a file object from url
     * @param $url string URL
     * @param $filename string Filename
     * @return $this
     */
    public function fromUrl($url, $filename = null)
    {
        $data = Http::get($url);

        if ($data->code !== 200) {
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
     * getPathAttribute helper attribute for getPath
     * @return string
     */
    public function getPathAttribute()
    {
        return $this->getPath();
    }

    /**
     * getExtensionAttribute helper attribute for getExtension
     * @return string
     */
    public function getExtensionAttribute()
    {
        return $this->getExtension();
    }

    /**
     * setDataAttribute used only when filling attributes
     */
    public function setDataAttribute($value)
    {
        $this->data = $value;
    }

    /**
     * getWidthAttribute helper attribute for get image width
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
     * getHeightAttribute helper attribute for get image height
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
     * getSizeAttribute helper attribute for file size in human format
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
     * output the raw file contents
     * @param string $disposition The Content-Disposition to set, defaults to inline
     * @param bool $returnResponse
     * @return Response|void
     */
    public function output($disposition = 'inline', $returnResponse = false)
    {
        $response = Response::make($this->getContents())->withHeaders([
            'Content-type' => $this->getContentType(),
            'Content-Disposition' => $disposition . '; filename="' . $this->file_name . '"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0',
            'Accept-Ranges' => 'bytes',
            'Content-Length' => $this->file_size,
        ]);

        if ($returnResponse) {
            return $response;
        }

        $response->sendHeaders();
        $response->sendContent();
    }

    /**
     * outputThumb the raw thumb file contents
     * @param integer $width
     * @param integer $height
     * @param array $options [
     *     'mode'      => 'auto',
     *     'offset'    => [0, 0],
     *     'quality'   => 90,
     *     'sharpen'   => 0,
     *     'interlace' => false,
     *     'extension' => 'auto',
     *     'disposition' => 'inline',
     * ]
     * @param bool $returnResponse
     * @return Response|void
     */
    public function outputThumb($width, $height, $options = [], $returnResponse = false)
    {
        $disposition = array_get($options, 'disposition', 'inline');
        $options = $this->getDefaultThumbOptions($options);

        // Generate thumb if not existing already
        $thumbFile = $this->getThumbFilename($width, $height, $options);
        if (
            !$this->hasFile($thumbFile) &&
            !$this->getThumb($width, $height, $options)
        ) {
            throw new Exception(sprintf('Thumb file "%s" failed to generate. Check error logs for more details.', $thumbFile));
        }

        $contents = $this->getContents($thumbFile);

        $response = Response::make($contents)->withHeaders([
            'Content-type' => $this->getContentType(),
            'Content-Disposition' => $disposition . '; filename="' . basename($thumbFile) . '"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0',
            'Accept-Ranges' => 'bytes',
            'Content-Length' => mb_strlen($contents, '8bit'),
        ]);

        if ($returnResponse) {
            return $response;
        }

        $response->sendHeaders();
        $response->sendContent();
    }

    //
    // Getters
    //

    /**
     * getCacheKey returns the cache key used for the hasFile method
     * @param string $path The path to get the cache key for
     * @return string
     */
    public function getCacheKey($path = null)
    {
        if (empty($path)) {
            $path = $this->getDiskPath();
        }

        return 'database-file::' . $path;
    }

    /**
     * getFilename returns the file name without path
     */
    public function getFilename()
    {
        return $this->file_name;
    }

    /**
     * getExtension returns the file extension
     */
    public function getExtension()
    {
        return FileHelper::extension($this->file_name);
    }

    /**
     * getLastModified returns the last modification date as a UNIX timestamp
     * @return int
     */
    public function getLastModified($fileName = null)
    {
        return $this->storageCmd('lastModified', $this->getDiskPath($fileName));
    }

    /**
     * getContentType returns the file content type
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
     * getContents from storage device
     */
    public function getContents($fileName = null)
    {
        return $this->storageCmd('get', $this->getDiskPath($fileName));
    }

    /**
     * getPath returns the public address to access the file
     */
    public function getPath($fileName = null)
    {
        if (empty($fileName)) {
            $fileName = $this->disk_name;
        }

        return $this->getPublicPath() . $this->getPartitionDirectory() . $fileName;
    }

    /**
     * getLocalPath returns a local path to this file. If the file is stored remotely,
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
     * getDiskPath returns the path to the file, relative to the storage disk
     * @return string
     */
    public function getDiskPath($fileName = null)
    {
        if (empty($fileName)) {
            $fileName = $this->disk_name;
        }

        return $this->getStorageDirectory() . $this->getPartitionDirectory() . $fileName;
    }

    /**
     * isPublic determines if the file is flagged "public" or not
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
     * sizeToString returns the file size as string
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
     * beforeSave check if new file data has been supplied
     * eg: $model->data = Input::file('something');
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
     * afterDelete clean up it's thumbnails
     */
    public function afterDelete()
    {
        try {
            $this->deleteThumbs();
            $this->deleteFile();
        }
        catch (Exception $ex) {
        }
    }

    //
    // Image handling
    //

    /**
     * isImage checks if the file extension is an image and returns true or false
     */
    public function isImage()
    {
        return in_array(strtolower($this->getExtension()), static::$imageExtensions);
    }

    /**
     * getImageDimensions
     * @return array|bool
     */
    protected function getImageDimensions()
    {
        return getimagesize($this->getLocalPath());
    }

    /**
     * getThumb generates and returns a thumbnail path
     *
     * @param integer $width
     * @param integer $height
     * @param array $options [
     *     'mode'      => 'auto',
     *     'offset'    => [0, 0],
     *     'quality'   => 90,
     *     'sharpen'   => 0,
     *     'interlace' => false,
     *     'extension' => 'auto',
     * ]
     * @return string The URL to the generated thumbnail
     */
    public function getThumb($width, $height, $options = [])
    {
        if (!$this->isImage() || !$this->hasFile($this->disk_name)) {
            return $this->getPath();
        }

        $width = (int) $width;
        $height = (int) $height;

        $options = $this->getDefaultThumbOptions($options);

        $thumbFile = $this->getThumbFilename($width, $height, $options);
        $thumbPath = $this->getDiskPath($thumbFile);
        $thumbPublic = $this->getPath($thumbFile);

        if (!$this->hasFile($thumbFile)) {
            try {
                if ($this->isLocalStorage()) {
                    $this->makeThumbLocal($thumbFile, $thumbPath, $width, $height, $options);
                }
                else {
                    $this->makeThumbStorage($thumbFile, $thumbPath, $width, $height, $options);
                }
            }
            catch (Exception $ex) {
                Log::error($ex);
                return '';
            }
        }

        return $thumbPublic;
    }

    /**
     * getThumbFilename generates a thumbnail filename
     * @return string
     */
    public function getThumbFilename($width, $height, $options)
    {
        $options = $this->getDefaultThumbOptions($options);
        return 'thumb_' . $this->id . '_' . $width . '_' . $height . '_' . $options['offset'][0] . '_' . $options['offset'][1] . '_' . $options['mode'] . '.' . $options['extension'];
    }

    /**
     * getDefaultThumbOptions returns the default thumbnail options
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

        if (strtolower($options['extension']) === 'auto') {
            $options['extension'] = strtolower($this->getExtension());
        }

        return $options;
    }

    /**
     * makeThumbLocal generates the thumbnail based on the local file system. This step
     * is necessary to simplify things and ensure the correct file permissions are given
     * to the local files.
     */
    protected function makeThumbLocal($thumbFile, $thumbPath, $width, $height, $options)
    {
        $rootPath = $this->getLocalRootPath();
        $filePath = $rootPath.'/'.$this->getDiskPath();
        $thumbPath = $rootPath.'/'.$thumbPath;

        /*
         * Generate thumbnail
         */
        Resizer::open($filePath)
            ->resize($width, $height, $options)
            ->save($thumbPath)
        ;

        FileHelper::chmod($thumbPath);
    }

    /**
     * makeThumbStorage generates the thumbnail based on a remote storage engine
     */
    protected function makeThumbStorage($thumbFile, $thumbPath, $width, $height, $options)
    {
        $tempFile = $this->getLocalTempPath();
        $tempThumb = $this->getLocalTempPath($thumbFile);

        /*
         * Generate thumbnail
         */
        $this->copyStorageToLocal($this->getDiskPath(), $tempFile);

        try {
            Resizer::open($tempFile)
                ->resize($width, $height, $options)
                ->save($tempThumb)
            ;
        }
        finally {
            FileHelper::delete($tempFile);
        }

        /*
         * Publish to storage and clean up
         */
        $this->copyLocalToStorage($tempThumb, $thumbPath);

        FileHelper::delete($tempThumb);
    }

    /**
     * deleteThumbs deletes all thumbnails for this file
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
                $this->getDisk()->delete($collection);
            }
        }
    }

    //
    // File handling
    //

    /**
     * getDiskName generates a disk name from the supplied file name
     */
    protected function getDiskName()
    {
        if ($this->disk_name !== null) {
            return $this->disk_name;
        }

        $ext = strtolower($this->getExtension());
        $name = str_replace('.', '', uniqid(null, true));

        return $this->disk_name = !empty($ext) ? $name.'.'.$ext : $name;
    }

    /**
     * getLocalTempPath returns a temporary local path to work from
     */
    protected function getLocalTempPath($path = null)
    {
        if (!$path) {
            return $this->getTempPath() . '/' . md5($this->getDiskPath()) . '.' . $this->getExtension();
        }

        return $this->getTempPath() . '/' . $path;
    }

    /**
     * putFile saves a file
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
     * deleteFile contents from storage device
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

        // Clear remote storage cache
        if (!$this->isLocalStorage()) {
            Cache::forget($this->getCacheKey($filePath));
        }

        $this->deleteEmptyDirectory($directory);
    }

    /**
     * hasFile checks file exists on storage device
     */
    protected function hasFile($fileName = null)
    {
        $filePath = $this->getDiskPath($fileName);

        if ($this->isLocalStorage()) {
            return $this->storageCmd('exists', $filePath);
        }

        // Cache remote storage results for performance increase
        $result = Cache::rememberForever($this->getCacheKey($filePath), function () use ($filePath) {
            return $this->storageCmd('exists', $filePath);
        });

        // Forget negative results
        if (!$result) {
            Cache::forget($this->getCacheKey($filePath));
        }

        return $result;
    }

    /**
     * deleteEmptyDirectory checks if directory is empty then deletes it,
     * three levels up to match the partition directory.
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
     * isDirectoryEmpty returns true if a directory contains no files
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
     * storageCmd calls a method against File or Storage depending on local storage
     * This allows local storage outside the storage/app folder and is
     * also good for performance. For local storage, *every* argument
     * is prefixed with the local root path. Props to Laravel for
     * the unified interface.
     */
    protected function storageCmd()
    {
        $args = func_get_args();
        $command = array_shift($args);
        $result = null;

        if ($this->isLocalStorage()) {
            $interface = 'File';
            $path = $this->getLocalRootPath();
            $args = array_map(function ($value) use ($path) {
                return $path . '/' . $value;
            }, $args);

            $result = forward_static_call_array([$interface, $command], $args);
        }
        else {
            $result = call_user_func_array([$this->getDisk(), $command], $args);
        }

        return $result;
    }

    /**
     * copyStorageToLocal file
     */
    protected function copyStorageToLocal($storagePath, $localPath)
    {
        return FileHelper::put($localPath, $this->getDisk()->get($storagePath));
    }

    /**
     * copyLocalToStorage file
     */
    protected function copyLocalToStorage($localPath, $storagePath)
    {
        return $this->getDisk()->put($storagePath, FileHelper::get($localPath), $this->isPublic() ? 'public' : null);
    }

    //
    // Configuration
    //

    /**
     * getMaxFilesize returns the maximum size of an uploaded file as configured in php.ini
     * @return int The maximum size of an uploaded file in kilobytes
     */
    public static function getMaxFilesize()
    {
        return round(UploadedFile::getMaxFilesize() / 1024);
    }

    /**
     * getStorageDirectory defines the internal storage path, override this method
     */
    public function getStorageDirectory()
    {
        if ($this->isPublic()) {
            return 'uploads/public/';
        }

        return 'uploads/protected/';
    }

    /**
     * getPublicPath defines the public address for the storage path
     */
    public function getPublicPath()
    {
        if ($this->isPublic()) {
            return 'http://localhost/uploads/public/';
        }

        return 'http://localhost/uploads/protected/';
    }

    /**
     * getTempPath defines the internal working path, override this method
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
     * getDisk returns the storage disk the file is stored on
     * @return FilesystemAdapter
     */
    public function getDisk()
    {
        return Storage::disk();
    }

    /**
     * isLocalStorage returns true if the storage engine is local
     */
    protected function isLocalStorage()
    {
        return Storage::getDefaultDriver() === 'local';
    }

    /**
    * getPartitionDirectory generates a partition for the file
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
     * getLocalRootPath if working with local storage, determine the absolute local path
     */
    protected function getLocalRootPath()
    {
        return storage_path() . '/app';
    }
}
