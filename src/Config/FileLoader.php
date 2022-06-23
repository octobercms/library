<?php namespace October\Rain\Config;

use Illuminate\Filesystem\Filesystem;

use Exception;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Config\Repository as RepositoryContract;
use Illuminate\Contracts\Foundation\Application;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

class FileLoader
{
    /**
     * fromPath returns config files in a given path
     */
    public static function fromPath($path)
    {
        return self::getConfigurationFiles($path);
    }

    /**
     * Get all of the configuration files for the application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return array
     */
    protected static function getConfigurationFiles(string $path)
    {
        $files = [];

        $configPath = realpath($path);
        if (!$configPath) {
            return $files;
        }

        foreach (Finder::create()->files()->name('*.php')->in($configPath) as $file) {
            $directory = self::getNestedDirectory($file, $configPath);

            $files[$directory.basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        ksort($files, SORT_NATURAL);

        return $files;
    }

    /**
     * Get the configuration file nesting path.
     *
     * @param  \SplFileInfo  $file
     * @param  string  $configPath
     * @return string
     */
    protected static function getNestedDirectory(SplFileInfo $file, $configPath)
    {
        $directory = $file->getPath();

        if ($nested = trim(str_replace($configPath, '', $directory), DIRECTORY_SEPARATOR)) {
            $nested = str_replace(DIRECTORY_SEPARATOR, '.', $nested).'.';
        }

        return $nested;
    }
}