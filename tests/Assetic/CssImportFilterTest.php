<?php

use October\Rain\Assetic\Asset\FileAsset;
use October\Rain\Assetic\Asset\AssetCache;
use October\Rain\Assetic\Cache\CacheInterface;
use October\Rain\Assetic\Filter\CssImportFilter;
use October\Rain\Assetic\Factory\AssetFactory;

class CssImportFilterTest extends TestCase
{
    protected $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/rain-css-import-'.bin2hex(random_bytes(8));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->directory);
        parent::tearDown();
    }

    public function testRejectsAnIndirectImportCycle()
    {
        $this->writeCss('a.css', "@import 'b.css';");
        $this->writeCss('b.css', "@import './a.css';");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Circular CSS import');
        $this->asset('a.css', new BoundedCssImportFilter)->dump();
    }

    public function testRejectsADirectImportCycle()
    {
        $this->writeCss('a.css', "@import './a.css'; body { color: red; }");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Circular CSS import');
        $this->asset('a.css', new BoundedCssImportFilter)->dump();
    }

    public function testHashingRejectsAnImportCycle()
    {
        $root = $this->writeCss('a.css', "@import 'b.css';");
        $this->writeCss('b.css', "@import './a.css';");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Circular CSS import');
        (new CssImportFilter)->hashAsset($root, $this->directory);
    }

    public function testRepeatedImportsRetainOrderAndRewriteNestedUrls()
    {
        $this->writeCss('root.css', "@import 'nested/first.css'; @import 'shared.css';");
        $this->writeCss('nested/first.css', "@import '../shared.css'; .first { background: url(images/first.png); }");
        $this->writeCss('shared.css', '.shared { color: red; }');

        $this->assertSame(
            '.shared { color: red; } .first { background: url(nested/images/first.png); } .shared { color: red; }',
            $this->asset('root.css')->dump()
        );
    }

    public function testDependencyAliasesAreVisitedOnlyOnce()
    {
        $root = $this->writeCss('root.css', "@import 'shared.css'; @import './shared.css';");
        $this->writeCss('shared.css', '.shared {}');
        $filter = new CssImportFilter;

        $children = $filter->getAllChildren(new AssetFactory($this->directory), file_get_contents($root), $this->directory);

        $this->assertCount(1, $children);
        $this->assertSame('shared.css', $children[0]->getSourcePath());
    }

    public function testLeavesMissingAndNonCssImportsUnchanged()
    {
        $content = "@import 'missing.css'; @import 'notes.txt';";
        $this->writeCss('root.css', $content);
        $this->writeCss('notes.txt', 'not a stylesheet');

        $this->assertSame($content, $this->asset('root.css')->dump());
    }

    public function testLimitsADeepImportChain()
    {
        $this->writeCss('a.css', "@import 'b.css';");
        $this->writeCss('b.css', "@import 'c.css';");
        $this->writeCss('c.css', '.leaf {}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CSS import depth limit');
        $this->asset('a.css', new ShallowCssImportFilter)->dump();
    }

    public function testLimitsRepeatedImportExpansion()
    {
        $this->writeCss('root.css', "@import 'shared.css'; @import 'shared.css'; @import 'shared.css';");
        $this->writeCss('shared.css', '.shared {}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CSS import count limit');
        $this->asset('root.css', new SmallCssImportFilter)->dump();
    }

    public function testAllowsExpansionAtTheImportCountLimit()
    {
        $this->writeCss('root.css', "@import 'shared.css'; @import 'shared.css';");
        $this->writeCss('shared.css', '.shared {}');

        $this->assertSame('.shared {} .shared {}', $this->asset('root.css', new SmallCssImportFilter)->dump());
    }

    public function testLimitsDependencyTraversalDepth()
    {
        $root = $this->writeCss('a.css', "@import 'b.css';");
        $this->writeCss('b.css', "@import 'c.css';");
        $this->writeCss('c.css', '.leaf {}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CSS import depth limit');
        (new ShallowCssImportFilter)->hashAsset($root, $this->directory);
    }

    public function testLimitsDependencyTraversalCount()
    {
        $root = $this->writeCss('root.css', "@import 'a.css'; @import 'b.css'; @import 'c.css';");
        $this->writeCss('a.css', '.a {}');
        $this->writeCss('b.css', '.b {}');
        $this->writeCss('c.css', '.c {}');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('CSS import count limit');
        (new SmallCssImportFilter)->hashAsset($root, $this->directory);
    }

    public function testAllowsDependencyTraversalAtTheCountLimit()
    {
        $root = $this->writeCss('root.css', "@import 'a.css'; @import 'b.css';");
        $this->writeCss('a.css', '.a {}');
        $this->writeCss('b.css', '.b {}');

        $children = (new SmallCssImportFilter)->getAllChildren(
            new AssetFactory($this->directory), file_get_contents($root), $this->directory
        );
        $this->assertCount(2, $children);
    }

    public function testDeepDependencyEditInvalidatesCachedOutput()
    {
        $root = $this->writeCss('root.css', "@import 'one.css';");
        $this->writeCss('one.css', "@import 'nested/two.css';");
        $this->writeCss('nested/two.css', "@import 'three.css';");
        $leaf = $this->writeCss('nested/three.css', 'body { color: red; }');
        touch($leaf, 1600000000);
        clearstatcache();

        $cache = new CssImportMemoryCache;
        $first = new CssImportFilter;
        $first->setHash($first->hashAsset($root, $this->directory));
        $this->assertSame('body { color: red; }', (new AssetCache($this->asset('root.css', $first), $cache))->dump());

        file_put_contents($leaf, 'body { color: blue; }');
        touch($leaf, 1600000300);
        clearstatcache();

        $second = new CssImportFilter;
        $second->setHash($second->hashAsset($root, $this->directory));
        $this->assertSame('body { color: blue; }', (new AssetCache($this->asset('root.css', $second), $cache))->dump());
    }

    protected function writeCss($name, $content)
    {
        $path = $this->directory.'/'.$name;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $content);
        return $path;
    }

    protected function asset($name, $filter = null)
    {
        return new FileAsset($this->directory.'/'.$name, [$filter ?: new CssImportFilter], $this->directory, $name);
    }
}

/**
 * Stop a regression from hanging the test runner before its assertion can fail.
 */
class BoundedCssImportFilter extends CssImportFilter
{
    protected $passes = 0;

    protected function filterImports(string $content, callable $callback, bool $includeUrl = true): string
    {
        if (++$this->passes > 12) {
            throw new RuntimeException('CSS import expansion did not terminate.');
        }
        return parent::filterImports($content, $callback, $includeUrl);
    }
}

class CssImportMemoryCache implements CacheInterface
{
    protected $values = [];

    public function has($key)
    {
        return array_key_exists($key, $this->values);
    }

    public function get($key)
    {
        return $this->values[$key] ?? null;
    }

    public function set($key, $value)
    {
        $this->values[$key] = $value;
    }

    public function remove($key)
    {
        unset($this->values[$key]);
    }
}

class ShallowCssImportFilter extends CssImportFilter
{
    protected const MAX_IMPORT_DEPTH = 2;
}

class SmallCssImportFilter extends CssImportFilter
{
    protected const MAX_IMPORTS = 2;
}
