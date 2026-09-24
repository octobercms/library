<?php

use Illuminate\Filesystem\Filesystem;
use October\Rain\Translation\FileLoader;

class FileLoaderTest extends TestCase
{
    protected $path;
    protected $files;

    public function setUp(): void
    {
        parent::setUp();
        $this->files = new Filesystem;
        $this->path = sys_get_temp_dir().'/rain-translations-'.uniqid();
        $this->writeLines('plugin/en/messages.php', [
            'title' => 'Original',
            'nested' => ['first' => 'Original first', 'second' => 'Original second']
        ]);
    }

    public function tearDown(): void
    {
        $this->files->deleteDirectory($this->path);
        parent::tearDown();
    }

    public function testMissingLaterOverridePreservesEarlierOverride()
    {
        $this->writeLines('first/plugin/en/messages.php', ['title' => 'First override']);
        $loader = $this->loader(['first', 'second']);

        $this->assertSame('First override', $loader->load('en', 'messages', 'plugin')['title']);
    }

    public function testLaterOverridesMergeNestedValuesInPathOrder()
    {
        $this->writeLines('first/plugin/en/messages.php', [
            'title' => 'First override', 'nested' => ['first' => 'First path']
        ]);
        $this->writeLines('second/plugin/en/messages.php', [
            'title' => 'Second override', 'nested' => ['second' => 'Second path']
        ]);

        $this->assertSame([
            'title' => 'Second override',
            'nested' => ['first' => 'First path', 'second' => 'Second path']
        ], $this->loader(['first', 'second'])->load('en', 'messages', 'plugin'));
    }

    public function testOriginalLinesRemainWhenNoOverridesAreAvailable()
    {
        foreach ([[], ['missing'], ['first', 'second']] as $paths) {
            $this->assertSame([
                'title' => 'Original',
                'nested' => ['first' => 'Original first', 'second' => 'Original second']
            ], $this->loader($paths)->load('en', 'messages', 'plugin'));
        }
    }

    protected function loader(array $paths): FileLoader
    {
        $loader = new FileLoader($this->files, array_map(function ($path) {
            return $this->path.'/'.$path;
        }, $paths));
        $loader->addNamespace('plugin', $this->path.'/plugin');
        return $loader;
    }

    protected function writeLines($relativePath, array $lines)
    {
        $path = $this->path.'/'.$relativePath;
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, '<?php return '.var_export($lines, true).';');
    }
}
