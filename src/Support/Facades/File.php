<?php namespace October\Rain\Support\Facades;

use October\Rain\Support\Facade;

/**
 * @method static bool exists(string $path)
 * @method static string get(string $path, bool $lock)
 * @method static string sharedGet(string $path)
 * @method static mixed getRequire(string $path)
 * @method static mixed requireOnce(string $file)
 * @method static string hash(string $path)
 * @method static int put(string $path, string $contents, bool $lock = false)
 * @method static int prepend(string $path, string $data)
 * @method static int append(string $path, string $data)
 * @method static bool|void chmod(string $path, int $mode = null)
 * @method static bool delete(string|array $paths)
 * @method static bool move(string $path, string $target)
 * @method static bool copy(string $path, string $target)
 * @method static void link(string $target, string $link)
 * @method static string name(string $path)
 * @method static string basename(string $path)
 * @method static string dirname(string $path)
 * @method static string extension(string $path)
 * @method static string type(string $path)
 * @method static string|false mimeType(string $path)
 * @method static int size(string $path)
 * @method static int lastModified(string $path)
 * @method static bool isDirectory(string $directory)
 * @method static bool isReadable(string $path)
 * @method static bool isWritable(string $path)
 * @method static bool isFile(string $file)
 * @method static array glob(string $pattern, int $flags = 0)
 * @method static \Symfony\Component\Finder\SplFileInfo[] files(string $directory, bool $hidden = false)
 * @method static \Symfony\Component\Finder\SplFileInfo[] allFiles(string $directory, bool $hidden = false)
 * @method static array directories(string $directory)
 * @method static void ensureDirectoryExists(string $path, int $mode = 0755, bool $recursive = true)
 * @method static bool makeDirectory(string $path, int $mode = 0755, bool $recursive = false, bool $force = false)
 * @method static bool moveDirectory(string $from, string $to, bool $overwrite = false)
 * @method static bool copyDirectory(string $directory, string $destination, int $options = null)
 * @method static bool deleteDirectory(string $directory, bool $preserve = false)
 * @method static bool cleanDirectory(string $directory)
 * @method static bool isDirectoryEmpty(string $directory)
 * @method static string sizeToString(int $bytes)
 * @method static string localToPublic(string $path)
 * @method static bool isLocalPath(string $path, bool $realpath = true)
 * @method static string fromClass($classname)
 * @method static string|false existsInsensitive(string $path)
 * @method static string normalizePath(string $path)
 * @method static string symbolizePath(string $path, bool $default = false)
 * @method static bool isPathSymbol(string $path)
 * @method static bool|void chmodRecursive(string $path, $fileMask = null, $directoryMask = null)
 * @method static string|null getFilePermissions()
 * @method static string|null getFolderPermissions()
 * @method static bool fileNameMatch(string|array $fileName, string $pattern)
 *
 * @see \October\Rain\Filesystem\Filesystem
 */
class File extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'files';
    }
}
