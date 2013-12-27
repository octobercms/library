<?php namespace October\Rain\Filesystem;

use Illuminate\Filesystem\Filesystem as FilesystemBase;

/**
 * File helper
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
        foreach($files as $file) {
            if(strtolower($file) == $pathLower) {
                return $file;
            }
        }

        return false;
    }

    public function isDirectoryEmpty($dir)
    {
        if (!is_readable($dir))
            return null;

        $handle = opendir($dir);
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                return false;
                closedir($handle);
            }
        }

        closedir($handle);
        return true;
    }

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

}