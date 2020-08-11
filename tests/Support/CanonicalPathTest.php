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
        $drive = (preg_match('/^([A-Z]:)/', __FILE__, $matches) === 1)
            ? $matches[1]
            : null;

        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1'),
            resolve_path($dir . '/dir1')
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1'),
            realpath($dir . '/dir1')
        );

        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/subdir1'),
            resolve_path($dir . '/dir1/subdir1/')
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/subdir1'),
            realpath($dir . '/dir1/subdir1/')
        );

        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/subdir1/file1'),
            resolve_path($dir . '/dir1/subdir1/file1')
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/subdir1/file1'),
            realpath($dir . '/dir1/subdir1/file1')
        );

        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/file2'),
            resolve_path($dir . '/dir2/file2')
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/file2'),
            realpath($dir . '/dir2/file2')
        );

        // realpath() won't work for this (on Linux), as it does not normalise Windows directory separators
        if (DIRECTORY_SEPARATOR === '/') {
            $this->assertEquals(
                str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/subdir1/file1'),
                resolve_path($dir . '\\dir1\\subdir1\\file1')
            );
            $this->assertFalse(
                realpath($dir . '\\dir1\\subdir1\\file1')
            );
        }

        // realpath() won't work for this, as it does not resolve missing directories and files
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/subdir1/missing/missing2'),
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
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/spaced dir'),
            resolve_path($dir . '/spaced dir/')
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/spaced dir'),
            realpath($dir . '/spaced dir/')
        );

        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/spaced dir/spaced file'),
            resolve_path($dir . '/spaced dir/spaced file')
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/spaced dir/spaced file'),
            realpath($dir . '/spaced dir/spaced file')
        );
    }

    public function testRelativePaths()
    {
        $dir = dirname(__DIR__) . '/fixtures/paths';

        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1'),
            resolve_path($dir . '/dir1/./subdir1/..')
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1'),
            realpath($dir . '/dir1/./subdir1/..')
        );

        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1'),
            resolve_path($dir . '/dir1/subdir1/../')
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1'),
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
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1'),
            resolve_path($dir . '/dir1/subdir1/../../dir1/./../dir1/')
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1'),
            realpath($dir . '/dir1/subdir1/../../dir1/./../dir1/')
        );

        // realpath() won't work for this, as it does not resolve missing directories and files
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/missing/missing2'),
            resolve_path($dir . '/dir1/subdir1/../../dir1/./../dir1/missing/missing2')
        );
        $this->assertFalse(
            realpath($dir . '/dir1/subdir1/../../dir1/./../dir1/missing/missing2')
        );

        // realpath() won't work for this, as it does not resolve missing directories and files
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/missing/missing2'),
            resolve_path($dir . '/dir1/subdir1/missing/../../../dir1/./../dir1/missing/missing2')
        );
        $this->assertFalse(
            realpath($dir . '/dir1/subdir1/missing/../../../dir1/./../dir1/missing/missing2')
        );
    }

    public function testRelativeToWorkingDirPaths()
    {
        if (class_exists('System\ServiceProvider')) {
            $baseWorkingDir = './vendor/october/rain/';
        } else {
            $baseWorkingDir = './';
        }

        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, dirname(dirname(__DIR__)) . '/tests/fixtures/paths/dir1/subdir1'),
            resolve_path($baseWorkingDir . 'tests/fixtures/paths/dir1/subdir1')
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, dirname(dirname(__DIR__)) . '/tests/fixtures/paths/dir1/subdir1'),
            realpath($baseWorkingDir . 'tests/fixtures/paths/dir1/subdir1')
        );

        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, dirname(dirname(__DIR__)) . '/tests/fixtures/paths/dir1/subdir1'),
            resolve_path($baseWorkingDir . 'tests/../tests/fixtures/paths/dir1/subdir1')
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, dirname(dirname(__DIR__)) . '/tests/fixtures/paths/dir1/subdir1'),
            realpath($baseWorkingDir . 'tests/../tests/fixtures/paths/dir1/subdir1')
        );

        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, dirname(dirname(dirname(__DIR__)))),
            resolve_path($baseWorkingDir . '..')
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, dirname(dirname(dirname(__DIR__)))),
            realpath($baseWorkingDir . '..')
        );

        // realpath() won't work for this, as it does not resolve missing directories and files
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, dirname(dirname(__DIR__)) . '/tests/fixtures/paths/dir1/missing'),
            resolve_path($baseWorkingDir . 'tests/fixtures/paths/dir1/missing')
        );
        $this->assertFalse(
            realpath($baseWorkingDir . 'tests/fixtures/paths/dir1/missing')
        );
    }

    public function testAbsoluteSymlinks()
    {
        $dir = dirname(__DIR__) . '/fixtures/paths';

        // Create an absolute symlink
        if (file_exists(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2'))) {
            unlink(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2'));
        }
        symlink(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/subdir1'),
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2')
        );

        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/subdir1'),
            resolve_path($dir . '/dir2/link2')
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/subdir1'),
            realpath($dir . '/dir2/link2')
        );

        // realpath() won't work for this, as it does not resolve missing directories and files
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/subdir1/missing/missing2'),
            resolve_path($dir . '/dir2/link2/missing/missing2')
        );
        $this->assertFalse(
            realpath($dir . '/dir2/link2/missing/missing2')
        );

        // realpath() won't work for this, as it does not resolve missing directories and files
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/missing'),
            resolve_path($dir . '/dir2/link2/../missing')
        );
        $this->assertFalse(
            realpath($dir . '/dir2/link2/../missing')
        );

        // Remove test symlink
        unlink(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2'));
    }

    public function testRelativeSymlinks()
    {
        $dir = dirname(__DIR__) . '/fixtures/paths';

        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/subdir1'),
            resolve_path($dir . '/dir3/link3')
        );
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/subdir1'),
            realpath($dir . '/dir3/link3')
        );

        // realpath() won't work for this, as it does not resolve missing directories and files
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/subdir1/missing/missing2'),
            resolve_path($dir . '/dir3/link3/missing/missing2')
        );
        $this->assertFalse(
            realpath($dir . '/dir3/link3/missing/missing2')
        );

        // realpath() won't work for this, as it does not resolve missing directories and files
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/missing'),
            resolve_path($dir . '/dir3/link3/../missing')
        );
        $this->assertFalse(
            realpath($dir . '/dir3/link3/../missing')
        );
    }
}
