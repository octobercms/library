<?php namespace October\Rain\Filesystem;

use Illuminate\Filesystem\Filesystem as FilesystemBase;
use ReflectionClass;
use FilesystemIterator;

/**
 * File helper
 *
 * @package october\filesystem
 * @author Alexey Bobkov, Samuel Georges
 */
class Filesystem extends FilesystemBase
{

    /**
     * @var string Default file permission mask as a string ("777").
     */
    public $filePermissions = null;

    /**
     * @var string Default folder permission mask as a string ("777").
     */
    public $folderPermissions = null;

    /**
     * Determine if the given path contains no files.
     * @param  string  $directory
     * @return bool
     */
    public function isDirectoryEmpty($directory)
    {
        if (!is_readable($directory))
            return null;

        $handle = opendir($directory);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != '.' && $entry != '..') {
                closedir($handle);
                return false;
            }
        }

        closedir($handle);
        return true;
    }

    /**
     * Modify file/folder permissions recursively
     * @param  string $path
     * @param  octal $fileMask
     * @param  octal $directoryMask
     * @return void
     */
    public function chmodRecursive($path, $fileMask, $directoryMask = null)
    {
        if (!$this->isDirectory($path))
            return @chmod($path, $fileMask);

        if (!$directoryMask)
            $directoryMask = $fileMask;

        $items = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);
        foreach ($items as $item) {
            if ($item->isDir()) {
                $_path = $item->getPathname();
                @chmod($_path, $directoryMask);
                $this->chmodRecursive($_path, $fileMask, $directoryMask);
            }
            else {
                @chmod($item->getPathname(), $fileMask);
            }
        }
    }

    /**
     * Converts a file size in bytes to human readable format.
     * @param  int $bytes
     * @return string
     */
    public function sizeToString($bytes)
    {
        if ($bytes >= 1073741824)
            return number_format($bytes / 1073741824, 2) . ' GB';

        if ($bytes >= 1048576)
            return number_format($bytes / 1048576, 2) . ' MB';

        if ($bytes >= 1024)
            return $bytes = number_format($bytes / 1024, 2) . ' KB';

        if ($bytes > 1)
            return $bytes = $bytes . ' bytes';

        if ($bytes == 1)
            return $bytes . ' byte';

        return '0 bytes';
    }

    /**
     * Returns a public file path from an absolute one
     * eg: /home/mysite/public_html/welcome -> /welcome
     * @param  string $path Absolute path
     * @return string
     */
    public static function localToPublic($path)
    {
        $result = null;
        $publicPath = public_path();

        if (strpos($path, $publicPath) === 0)
            $result = str_replace("\\", "/", substr($path, strlen($publicPath)));

        return $result;
    }

    /**
     * Finds the path to a class
     * @param  mixed  $className Class name or object
     * @return string The file path
     */
    public static function fromClass($className)
    {
        $reflector = new ReflectionClass($className);
        return $reflector->getFileName();
    }

    /**
     * Determine if a file exists with case insensitivity
     * supported for the file only.
     * @param  string $path
     * @return mixed  Sensitive path or false
     */
    public function existsInsensitive($path)
    {
        if (self::exists($path))
            return $path;

        $directoryName = dirname($path);
        $pathLower = strtolower($path);

        if (!$files = self::glob($directoryName . '/*', GLOB_NOSORT))
            return false;

        foreach ($files as $file) {
            if (strtolower($file) == $pathLower) {
                return $file;
            }
        }

        return false;
    }

    /**
     * Normalizes the directory separator, often used by Win systems.
     * @param  string $path Path name
     * @return string       Normalized path
     */
    public function normalizePath($path)
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * Write the contents of a file.
     * @param  string  $path
     * @param  string  $contents
     * @return int
     */
    public function put($path, $contents)
    {
        $result = parent::put($path, $contents);
        if ($mask = $this->getFilePermissions()) @chmod($path, $mask);
        return $result;
    }

    /**
     * Create a directory.
     * @param  string  $path
     * @param  int     $mode
     * @param  bool    $recursive
     * @param  bool    $force
     * @return bool
     */
    public function makeDirectory($path, $mode = 0777, $recursive = false, $force = false)
    {
        if ($mask = $this->getFolderPermissions())
            $mode = $mask;

        return parent::makeDirectory($path, $mode, $recursive, $force);
    }

    /**
     * Returns the default file permission mask to use.
     * @return string Permission mask as octal (0777) or null
     */
    public function getFilePermissions()
    {
        return $this->filePermissions
            ? octdec($this->filePermissions)
            : null;
    }

    /**
     * Returns the default folder permission mask to use.
     * @return string Permission mask as octal (0777) or null
     */
    public function getFolderPermissions()
    {
        return $this->folderPermissions
            ? octdec($this->folderPermissions)
            : null;
    }

}