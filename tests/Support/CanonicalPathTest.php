<?php

/**
 * The tests below will test both the resolve_path() method, and the original realpath() method to ensure we maintain
 * parity with the expected functionality of realpath(), unless where we've deviated for flexibility.
 */
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
            $dir . '/dir1',
            realpath($dir . '/dir1')
        );

        $this->assertEquals(
            $dir . '/dir1/subdir1',
            resolve_path($dir . '/dir1/subdir1/')
        );
        $this->assertEquals(
            $dir . '/dir1/subdir1',
            realpath($dir . '/dir1/subdir1/')
        );

        $this->assertEquals(
            $dir . '/dir1/subdir1/file1',
            resolve_path($dir . '/dir1/subdir1/file1')
        );
        $this->assertEquals(
            $dir . '/dir1/subdir1/file1',
            realpath($dir . '/dir1/subdir1/file1')
        );

        $this->assertEquals(
            $dir . '/dir2/file2',
            resolve_path($dir . '/dir2/file2')
        );
        $this->assertEquals(
            $dir . '/dir2/file2',
            realpath($dir . '/dir2/file2')
        );

        // realpath() won't work for this, as it does not normalise directory separators
        $this->assertEquals(
            $dir . '/dir1/subdir1/file1',
            resolve_path($dir . '\\dir1\\subdir1\\file1')
        );
        $this->assertFalse(
            realpath($dir . '\\dir1\\subdir1\\file1')
        );

        // realpath() won't work for this, as it does not resolve missing directories and files
        $this->assertEquals(
            $dir . '/dir1/subdir1/missing/missing2',
            resolve_path($dir . '/dir1/subdir1/missing/missing2')
        );
        $this->assertFalse(
            realpath($dir . '/dir1/subdir1/missing/missing2')
        );

        // Both functions should fail as you cannot relatively traverse a path further if it hits a file
        $this->assertFalse(
            resolve_path($dir . '/dir1/subdir1/file1/missing')
        );
        $this->assertFalse(
            realpath($dir . '/dir1/subdir1/file1/missing')
        );

        $this->assertEquals(
            '/tmp',
            resolve_path('/tmp/')
        );
        $this->assertEquals(
            '/tmp',
            realpath('/tmp/')
        );

        // Path /dev/null technically doesn't exist - we do return a result, but realpath() won't. This is a fringe case
        // that's unlikely to come up.
        $this->assertEquals(
            '/dev/null',
            resolve_path('\\dev\\null')
        );
        $this->assertFalse(
            realpath('\\dev\\null')
        );

        $this->assertEquals(
            $dir . '/spaced dir',
            resolve_path($dir . '/spaced dir/')
        );
        $this->assertEquals(
            $dir . '/spaced dir',
            realpath($dir . '/spaced dir/')
        );

        $this->assertEquals(
            $dir . '/spaced dir/spaced file',
            resolve_path($dir . '/spaced dir/spaced file')
        );
        $this->assertEquals(
            $dir . '/spaced dir/spaced file',
            realpath($dir . '/spaced dir/spaced file')
        );
    }

    public function testRelativePaths()
    {
        $dir = dirname(__DIR__) . '/fixtures/paths';

        $this->assertEquals(
            $dir . '/dir1',
            resolve_path($dir . '/dir1/./subdir1/..')
        );
        $this->assertEquals(
            $dir . '/dir1',
            realpath($dir . '/dir1/./subdir1/..')
        );

        $this->assertEquals(
            $dir . '/dir1',
            resolve_path($dir . '/dir1/subdir1/../')
        );
        $this->assertEquals(
            $dir . '/dir1',
            realpath($dir . '/dir1/subdir1/../')
        );

        // Both functions should fail as you cannot relatively traverse a path further if it hits a file
        $this->assertFalse(
            resolve_path($dir . '/dir1/subdir1/file1/../..')
        );
        $this->assertFalse(
            realpath($dir . '/dir1/subdir1/file1/../..')
        );

        $this->assertEquals(
            $dir . '/dir1',
            resolve_path($dir . '/dir1/subdir1/../../dir1/./../dir1/')
        );
        $this->assertEquals(
            $dir . '/dir1',
            realpath($dir . '/dir1/subdir1/../../dir1/./../dir1/')
        );

        // realpath() won't work for this, as it does not resolve missing directories and files
        $this->assertEquals(
            $dir . '/dir1/missing/missing2',
            resolve_path($dir . '/dir1/subdir1/../../dir1/./../dir1/missing/missing2')
        );
        $this->assertFalse(
            realpath($dir . '/dir1/subdir1/../../dir1/./../dir1/missing/missing2')
        );

        // realpath() won't work for this, as it does not resolve missing directories and files
        $this->assertEquals(
            $dir . '/dir1/missing/missing2',
            resolve_path($dir . '/dir1/subdir1/missing/../../../dir1/./../dir1/missing/missing2')
        );
        $this->assertFalse(
            realpath($dir . '/dir1/subdir1/missing/../../../dir1/./../dir1/missing/missing2')
        );
    }

    public function testRelativeToWorkingDirPaths()
    {
        // Working directory would be the root folder in this instance

        $this->assertEquals(
            dirname(dirname(__DIR__)) . '/tests/fixtures/paths/dir1/subdir1',
            resolve_path('./tests/fixtures/paths/dir1/subdir1')
        );
        $this->assertEquals(
            dirname(dirname(__DIR__)) . '/tests/fixtures/paths/dir1/subdir1',
            realpath('./tests/fixtures/paths/dir1/subdir1')
        );

        $this->assertEquals(
            dirname(dirname(__DIR__)) . '/tests/fixtures/paths/dir1/subdir1',
            resolve_path('./tests/../tests/fixtures/paths/dir1/subdir1')
        );
        $this->assertEquals(
            dirname(dirname(__DIR__)) . '/tests/fixtures/paths/dir1/subdir1',
            realpath('./tests/../tests/fixtures/paths/dir1/subdir1')
        );

        $this->assertEquals(
            dirname(dirname(dirname(__DIR__))),
            resolve_path('./..')
        );
        $this->assertEquals(
            dirname(dirname(dirname(__DIR__))),
            realpath('./..')
        );

        // realpath() won't work for this, as it does not resolve missing directories and files
        $this->assertEquals(
            dirname(dirname(__DIR__)) . '/tests/fixtures/paths/dir1/missing',
            resolve_path('./tests/fixtures/paths/dir1/missing')
        );
        $this->assertFalse(
            realpath('./tests/fixtures/paths/dir1/missing')
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
            $dir . '/dir1/subdir1',
            realpath($dir . '/dir2/link2')
        );

        // realpath() won't work for this, as it does not resolve missing directories and files
        $this->assertEquals(
            $dir . '/dir1/subdir1/missing/missing2',
            resolve_path($dir . '/dir2/link2/missing/missing2')
        );
        $this->assertFalse(
            realpath($dir . '/dir2/link2/missing/missing2')
        );

        // realpath() won't work for this, as it does not resolve missing directories and files
        $this->assertEquals(
            $dir . '/dir1/missing',
            resolve_path($dir . '/dir2/link2/../missing')
        );
        $this->assertFalse(
            realpath($dir . '/dir2/link2/../missing')
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
            $dir . '/dir1/subdir1',
            realpath($dir . '/dir3/link3')
        );

        // realpath() won't work for this, as it does not resolve missing directories and files
        $this->assertEquals(
            $dir . '/dir1/subdir1/missing/missing2',
            resolve_path($dir . '/dir3/link3/missing/missing2')
        );
        $this->assertFalse(
            realpath($dir . '/dir3/link3/missing/missing2')
        );

        // realpath() won't work for this, as it does not resolve missing directories and files
        $this->assertEquals(
            $dir . '/dir1/missing',
            resolve_path($dir . '/dir3/link3/../missing')
        );
        $this->assertFalse(
            realpath($dir . '/dir3/link3/../missing')
        );
    }
}
