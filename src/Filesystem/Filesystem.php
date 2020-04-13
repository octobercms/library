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
     * @var array Known path symbols and their prefixes.
     */
    public $pathSymbols = [];

    /**
     * Determine if the given path contains no files.
     * @param  string  $directory
     * @return bool
     */
    public function isDirectoryEmpty($directory)
    {
        if (!is_readable($directory)) {
            return null;
        }

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
     * Converts a file size in bytes to human readable format.
     * @param  int $bytes
     * @return string
     */
    public function sizeToString($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        if ($bytes > 1) {
            return $bytes . ' bytes';
        }

        if ($bytes == 1) {
            return $bytes . ' byte';
        }

        return '0 bytes';
    }

    /**
     * Returns a public file path from an absolute one
     * eg: /home/mysite/public_html/welcome -> /welcome
     * @param  string $path Absolute path
     * @return string
     */
    public function localToPublic($path)
    {
        $result = null;
        $publicPath = public_path();

        if (strpos($path, $publicPath) === 0) {
            $result = str_replace("\\", "/", substr($path, strlen($publicPath)));
        }
        // Attempt to support first level symlinks
        elseif ($directories = self::glob($publicPath . '/*', GLOB_NOSORT | GLOB_ONLYDIR)) {
            foreach ($directories as $dir) {
                if (is_link($dir) && strpos($path, readlink($dir)) === 0) {
                    // Get the path of the requested path relative to the symlink in the public path
                    $relativeLinkedPath = substr($path, strlen(readlink($dir)));
                    return str_replace("\\", "/", substr($dir, strlen($publicPath)) . $relativeLinkedPath);
                }
            }
        }

        return $result;
    }

    /**
     * Returns true if the specified path is within the path of the application
     * @param  string  $path The path to
     * @param  boolean $realpath Default true, uses realpath() to resolve the provided path before checking location. Set to false if you need to check if a potentially non-existent path would be within the application path
     * @return boolean
     */
    public function isLocalPath($path, $realpath = true)
    {
        $base = base_path();

        if ($realpath) {
            $path = realpath($path);
        }

        return !($path === false || strncmp($path, $base, strlen($base)) !== 0);
    }

    /**
     * Finds the path to a class
     * @param  mixed  $className Class name or object
     * @return string The file path
     */
    public function fromClass($className)
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
        if (self::exists($path)) {
            return $path;
        }

        $directoryName = dirname($path);
        $pathLower = strtolower($path);

        if (!$files = self::glob($directoryName . '/*', GLOB_NOSORT)) {
            return false;
        }

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
     * Converts a path using path symbol. Returns the original path if
     * no symbol is used and no default is specified.
     * @param  string $path
     * @param  mixed $default
     * @return string
     */
    public function symbolizePath($path, $default = false)
    {
        if (!$firstChar = $this->isPathSymbol($path)) {
            return $default === false ? $path : $default;
        }

        $_path = substr($path, 1);
        return $this->pathSymbols[$firstChar] . $_path;
    }

    /**
     * Returns true if the path uses a symbol.
     * @param  string  $path
     * @return boolean
     */
    public function isPathSymbol($path)
    {
        $firstChar = substr($path, 0, 1);
        if (isset($this->pathSymbols[$firstChar])) {
            return $firstChar;
        }

        return false;
    }

    /**
     * Write the contents of a file.
     * @param  string  $path
     * @param  string  $contents
     * @return int
     */
    public function put($path, $contents, $lock = false)
    {
        $result = parent::put($path, $contents, $lock);
        $this->chmod($path);
        return $result;
    }

    /**
     * Copy a file to a new location.
     * @param  string  $path
     * @param  string  $target
     * @return bool
     */
    public function copy($path, $target)
    {
        $result = parent::copy($path, $target);
        $this->chmod($target);
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
        if ($mask = $this->getFolderPermissions()) {
            $mode = $mask;
        }

        /*
         * Find the green leaves
         */
        if ($recursive && $mask) {
            $chmodPath = $path;
            while (true) {
                $basePath = dirname($chmodPath);
                if ($chmodPath == $basePath) {
                    break;
                }
                if ($this->isDirectory($basePath)) {
                    break;
                }
                $chmodPath = $basePath;
            }
        }
        else {
            $chmodPath = $path;
        }

        /*
         * Make the directory
         */
        $result = parent::makeDirectory($path, $mode, $recursive, $force);

        /*
         * Apply the permissions
         */
        if ($mask) {
            $this->chmod($chmodPath, $mask);

            if ($recursive) {
                $this->chmodRecursive($chmodPath, null, $mask);
            }
        }

        return $result;
    }

    /**
     * Modify file/folder permissions
     * @param  string $path
     * @param  octal $mask
     * @return void
     */
    public function chmod($path, $mask = null)
    {
        if (!$mask) {
            $mask = $this->isDirectory($path)
                ? $this->getFolderPermissions()
                : $this->getFilePermissions();
        }

        if (!$mask) {
            return;
        }

        return @chmod($path, $mask);
    }

    /**
     * Modify file/folder permissions recursively
     * @param  string $path
     * @param  octal $fileMask
     * @param  octal $directoryMask
     * @return void
     */
    public function chmodRecursive($path, $fileMask = null, $directoryMask = null)
    {
        if (!$fileMask) {
            $fileMask = $this->getFilePermissions();
        }

        if (!$directoryMask) {
            $directoryMask = $this->getFolderPermissions() ?: $fileMask;
        }

        if (!$fileMask) {
            return;
        }

        if (!$this->isDirectory($path)) {
            return $this->chmod($path, $fileMask);
        }

        $items = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);
        foreach ($items as $item) {
            if ($item->isDir()) {
                $_path = $item->getPathname();
                $this->chmod($_path, $directoryMask);
                $this->chmodRecursive($_path, $fileMask, $directoryMask);
            }
            else {
                $this->chmod($item->getPathname(), $fileMask);
            }
        }
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

    /**
     * Match filename against a pattern.
     * @param  string|array $fileName
     * @param  string $pattern
     * @return bool
     */
    public function fileNameMatch($fileName, $pattern)
    {
        if ($pattern === $fileName) {
            return true;
        }

        $regex = strtr(preg_quote($pattern, '#'), ['\*' => '.*', '\?' => '.']);

        return (bool) preg_match('#^' . $regex . '$#i', $fileName);
    }
}
