<?php

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use October\Rain\Database\Attach\File as Attachment;

class FileCacheTest extends TestCase
{
    protected $savedFacadeApp;
    protected $savedResolver;
    protected $savedDispatcher;
    protected $cache;
    protected $file;
    protected $tempPath;

    public function setUp(): void
    {
        parent::setUp();

        $this->savedFacadeApp = Facade::getFacadeApplication();
        $this->savedResolver = Attachment::getConnectionResolver();
        $this->savedDispatcher = Attachment::getEventDispatcher();

        $app = new Container;
        $app->instance('config', new Repository([
            'cache' => [
                'default' => 'array',
                'stores' => ['array' => ['driver' => 'array']],
            ],
        ]));
        $this->cache = new CacheManager($app);
        $app->instance('cache', $this->cache);
        $app->instance('files', new Filesystem);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);

        $capsule = new Capsule($app);
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $capsule->bootEloquent();

        $this->tempPath = sys_get_temp_dir() . '/october-file-cache-test-' . uniqid();
        mkdir($this->tempPath);

        $this->file = new FileCacheTestAttachment;
        $this->file->tempPath = $this->tempPath;
        $this->file->setRawAttributes([
            'id' => 42,
            'file_name' => 'photo.jpg',
            'disk_name' => 'abcdef123.jpg',
            'is_public' => true,
        ]);
        $this->file->disk = new FileCacheTestDisk;
        $this->file->disk->paths[$this->file->getDiskPath()] = true;
    }

    public function tearDown(): void
    {
        array_map('unlink', glob($this->tempPath . '/*'));
        rmdir($this->tempPath);

        if ($this->savedResolver) {
            Attachment::setConnectionResolver($this->savedResolver);
        }
        else {
            Attachment::unsetConnectionResolver();
        }
        if ($this->savedDispatcher) {
            Attachment::setEventDispatcher($this->savedDispatcher);
        }
        else {
            Attachment::unsetEventDispatcher();
        }

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->savedFacadeApp);

        parent::tearDown();
    }

    public function testPersistedMissingThumbnailIsGeneratedOnce()
    {
        $thumb = $this->file->getThumbFilename(100, 100, []);
        $key = $this->file->getCacheKey($this->file->getDiskPath($thumb));
        $this->cache->forever($key, false);

        $this->assertSame($this->file->getPath($thumb), $this->file->getThumb(100, 100));
        $this->assertSame($this->file->getPath($thumb), $this->file->getThumb(100, 100));
        $this->assertSame(1, $this->file->publications);
        $this->assertTrue($this->cache->get($key));
    }

    public function testColdMissingThumbnailIsGeneratedOnce()
    {
        $thumb = $this->file->getThumbFilename(100, 100, []);

        $this->assertSame($this->file->getPath($thumb), $this->file->getThumb(100, 100));
        $this->assertSame($this->file->getPath($thumb), $this->file->getThumb(100, 100));
        $this->assertSame(1, $this->file->publications);
    }

    public function testDeletingThumbnailInvalidatesMemoizedExistence()
    {
        $thumb = $this->file->getThumbFilename(100, 100, []);
        $path = $this->file->getDiskPath($thumb);
        $key = $this->file->getCacheKey($path);
        $this->file->disk->paths[$path] = true;
        $this->cache->forever($key, true);

        $this->assertSame($this->file->getPath($thumb), $this->file->getThumb(100, 100));
        $this->assertSame(0, $this->file->publications);

        $this->file->deleteThumbs();

        $this->assertArrayNotHasKey($path, $this->file->disk->paths);
        $this->assertNull($this->cache->get($key));
        $this->assertSame($this->file->getPath($thumb), $this->file->getThumb(100, 100));
        $this->assertSame(1, $this->file->publications);
    }

    public function testDeletingOriginalFileInvalidatesMemoizedExistence()
    {
        $key = $this->file->getCacheKey();
        $this->cache->forever($key, true);
        $this->assertTrue(self::callProtectedMethod($this->file, 'hasFile'));

        self::callProtectedMethod($this->file, 'deleteFile');

        $this->assertArrayNotHasKey($this->file->getDiskPath(), $this->file->disk->paths);
        $this->assertNull($this->cache->get($key));
        $this->assertFalse(self::callProtectedMethod($this->file, 'hasFile'));
        $this->assertSame($this->file->getUrl(), $this->file->getThumb(100, 100));
        $this->assertSame(0, $this->file->publications);
    }
}

/**
 * FileCacheTestAttachment replaces remote storage transfers with an in-memory disk.
 * Thumbnails are still generated by the real resizer from a small generated image.
 */
class FileCacheTestAttachment extends Attachment
{
    public $disk;
    public $tempPath;
    public $publications = 0;

    public function getDisk()
    {
        return $this->disk;
    }

    protected function isLocalStorage()
    {
        return false;
    }

    public function getTempPath()
    {
        return $this->tempPath;
    }

    protected function copyStorageToLocal($storagePath, $localPath)
    {
        return imagejpeg(imagecreatetruecolor(4, 4), $localPath);
    }

    protected function copyLocalToStorage($localPath, $storagePath)
    {
        $this->publications++;
        $this->disk->paths[$storagePath] = true;

        return true;
    }
}

/**
 * FileCacheTestDisk keeps the remote file listing in memory.
 */
class FileCacheTestDisk
{
    public $paths = [];

    public function exists($path)
    {
        return isset($this->paths[$path]);
    }

    public function files($directory)
    {
        return array_values(array_filter(array_keys($this->paths), function ($path) use ($directory) {
            return str_starts_with($path, $directory);
        }));
    }

    public function allFiles($directory)
    {
        return $this->files($directory);
    }

    public function delete($paths)
    {
        foreach ((array) $paths as $path) {
            unset($this->paths[$path]);
        }

        return true;
    }

    public function deleteDirectory($directory)
    {
        return true;
    }
}
