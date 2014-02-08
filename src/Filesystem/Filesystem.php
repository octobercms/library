<?php namespace October\Rain\Filesystem;

use ReflectionClass;
use Illuminate\Filesystem\Filesystem as FilesystemBase;

/**
 * File helper
 *
 * @package october\filesystem
 * @author Alexey Bobkov, Samuel Georges
 */
class Filesystem extends FilesystemBase
{
    /**
     * Determine if a file exists with case insensitivity 
     * supported for the file only.
     * @param  string  $path
     * @return mixed Sensitive path or false
     */
    public function existsInsensitive($path)
    {
        if (self::exists($path))
            return $path;

        $directoryName = dirname($path);
        $pathLower = strtolower($path);

        $files = self::glob($directoryName . '/*', GLOB_NOSORT);
        foreach ($files as $file) {
            if (strtolower($file) == $pathLower) {
                return $file;
            }
        }

        return false;
    }

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
            if ($entry != "." && $entry != "..") {
                closedir($handle);
                return false;
            }
        }

        closedir($handle);
        return true;
    }

    /**
     * Converts a file size in bytes to human readable format.
     * @param int $bytes
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
     * @param string $path Absolute path
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
     * @param mixed $className Class name or object
     * @return string The file path
     */
    public static function fromClass($className)
    {
        $reflector = new ReflectionClass($className);
        return $reflector->getFileName();
    }

}