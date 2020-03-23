<?php namespace October\Rain\Assetic\Cache;

use File;
use October\Rain\Assetic\Cache\CacheInterface;
use RuntimeException;

/**
 * Assetic Filesystem Cache
 * Inherits the base logic except new files have permissions set.
 *
 * @package october/parse
 * @author Alexey Bobkov, Samuel Georges
 */
class FilesystemCache implements CacheInterface
{
    protected $dir;

    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    public function has($key)
    {
        return file_exists($this->dir.'/'.$key);
    }

    public function get($key)
    {
        $path = $this->dir.'/'.$key;

        if (!file_exists($path)) {
            throw new RuntimeException('There is no cached value for '.$key);
        }

        return file_get_contents($path);
    }

    public function set($key, $value)
    {
        if (!is_dir($this->dir) && false === @mkdir($this->dir, 0777, true)) {
            throw new RuntimeException('Unable to create directory '.$this->dir);
        }

        $path = $this->dir.'/'.$key;

        if (false === @file_put_contents($path, $value)) {
            throw new RuntimeException('Unable to write file '.$path);
        }

        File::chmod($path);
    }

    public function remove($key)
    {
        $path = $this->dir.'/'.$key;

        if (file_exists($path) && false === @unlink($path)) {
            throw new RuntimeException('Unable to remove file '.$path);
        }
    }
}
