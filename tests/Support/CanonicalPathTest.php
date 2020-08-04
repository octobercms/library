<?php

class CanonicalPathTest extends TestCase
{
    public function testDirectPaths()
    {
        $dir = dirname(__DIR__) . '/fixtures/paths';

        $this->assertEquals(
            $dir . '/dir1',
            canonical_path($dir . '/dir1')
        );
        $this->assertEquals(
            $dir . '/dir1/subdir1',
            canonical_path($dir . '/dir1/subdir1/')
        );
        $this->assertEquals(
            $dir . '/dir2/file2',
            canonical_path($dir . '/dir2/file2')
        );
        $this->assertEquals(
            $dir . '/dir1/subdir1/file1',
            canonical_path($dir . '\\dir1\\subdir1\\file1')
        );
        $this->assertEquals(
            $dir . '/dir1/subdir1/file1/missing',
            canonical_path($dir . '/dir1/subdir1/file1/missing')
        );
        $this->assertEquals(
            '/tmp',
            canonical_path('/tmp/')
        );
        $this->assertEquals(
            '/dev/null',
            canonical_path('\\dev\\null')
        );
    }

    public function testRelativePaths()
    {
        $dir = dirname(__DIR__) . '/fixtures/paths';

        $this->assertEquals(
            $dir . '/dir1',
            canonical_path($dir . '/dir1/./subdir/..')
        );
        $this->assertEquals(
            $dir . '/dir1',
            canonical_path($dir . '/dir1/subdir/../')
        );
        $this->assertEquals(
            $dir . '/dir1',
            canonical_path($dir . '/dir1/subdir/file1/../..')
        );
        $this->assertEquals(
            $dir . '/dir1',
            canonical_path($dir . '/dir1/subdir/file1/../../../dir1/./../dir1/')
        );
        $this->assertEquals(
            $dir . '/dir1/missing/missing2',
            canonical_path($dir . '/dir1/subdir/file1/../../../dir1/./../dir1/missing/missing2')
        );
    }

    public function testAbsoluteSymlinks()
    {
        $dir = dirname(__DIR__) . '/fixtures/paths';

        // Create an absolute symlink
        if (file_exists($dir . '/dir2/link2')) {
            unlink($dir . '/dir2/link2');
        }
        symlink($dir . '/dir1/subdir1', $dir . '/dir2/link2');

        $this->assertEquals(
            $dir . '/dir1/subdir1',
            canonical_path($dir . '/dir2/link2')
        );

        // Remove test symlink
        unlink($dir . '/dir2/link2');
    }
}
