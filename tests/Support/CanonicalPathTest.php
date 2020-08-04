<?php

class CanonicalPathTest extends TestCase
{
    public function testDirectPaths()
    {
        $dir = dirname(__DIR__) . '/fixtures/paths';

        $this->assertEquals(
            $dir . '/dir1',
            resolve_path($dir . '/dir1')
        );
        $this->assertEquals(
            $dir . '/dir1/subdir1',
            resolve_path($dir . '/dir1/subdir1/')
        );
        $this->assertEquals(
            $dir . '/dir2/file2',
            resolve_path($dir . '/dir2/file2')
        );
        $this->assertEquals(
            $dir . '/dir1/subdir1/file1',
            resolve_path($dir . '\\dir1\\subdir1\\file1')
        );
        $this->assertEquals(
            $dir . '/dir1/subdir1/file1/missing',
            resolve_path($dir . '/dir1/subdir1/file1/missing')
        );
        $this->assertEquals(
            '/tmp',
            resolve_path('/tmp/')
        );
        $this->assertEquals(
            '/dev/null',
            resolve_path('\\dev\\null')
        );
    }

    public function testRelativePaths()
    {
        $dir = dirname(__DIR__) . '/fixtures/paths';

        $this->assertEquals(
            $dir . '/dir1',
            resolve_path($dir . '/dir1/./subdir/..')
        );
        $this->assertEquals(
            $dir . '/dir1',
            resolve_path($dir . '/dir1/subdir/../')
        );
        $this->assertEquals(
            $dir . '/dir1',
            resolve_path($dir . '/dir1/subdir/file1/../..')
        );
        $this->assertEquals(
            $dir . '/dir1',
            resolve_path($dir . '/dir1/subdir/file1/../../../dir1/./../dir1/')
        );
        $this->assertEquals(
            $dir . '/dir1/missing/missing2',
            resolve_path($dir . '/dir1/subdir/file1/../../../dir1/./../dir1/missing/missing2')
        );
    }

    public function testRelativeToWorkingDirPaths()
    {
        // Working directory would be the root folder in this instance

        $this->assertEquals(
            dirname(dirname(__DIR__)) . '/dir1/subdir',
            resolve_path('./dir1/subdir')
        );

        $this->assertEquals(
            dirname(dirname(dirname(__DIR__))) . '/dir1/subdir',
            resolve_path('./../dir1/subdir')
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
            resolve_path($dir . '/dir2/link2')
        );

        $this->assertEquals(
            $dir . '/dir1/subdir1/missing/missing2',
            resolve_path($dir . '/dir2/link2/missing/missing2')
        );

        $this->assertEquals(
            $dir . '/dir1/missing',
            resolve_path($dir . '/dir2/link2/../missing')
        );

        // Remove test symlink
        unlink($dir . '/dir2/link2');
    }

    public function testRelativeSymlinks()
    {
        $dir = dirname(__DIR__) . '/fixtures/paths';

        $this->assertEquals(
            $dir . '/dir1/subdir1',
            resolve_path($dir . '/dir3/link3')
        );

        $this->assertEquals(
            $dir . '/dir1/subdir1/missing/missing2',
            resolve_path($dir . '/dir3/link3/missing/missing2')
        );

        $this->assertEquals(
            $dir . '/dir1/missing',
            resolve_path($dir . '/dir3/link3/../missing')
        );
    }
}
