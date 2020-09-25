<?php

use October\Rain\Filesystem\PathResolver;

/**
 * The tests below will test both the resolve_path() method (and wrapped PathResolver::resolve() method),
 * as well as the original realpath() method to ensure we maintain parity with the expected functionality of
 * realpath(), except for where we've deviated for flexibility.
 */
class PathResolverTest extends TestCase
{
    /** @var bool Whether we are testing on Windows, as Windows has some unique quirks for symlinks */
    protected $onWindows = false;

    public function setUp(): void
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->onWindows = true;
        }
    }

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
        if (!$this->onWindows) {
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
            if ($this->onWindows) {
                // Windows treats this symlink as a directory
                rmdir(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2'));
            } else {
                unlink(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2'));
            }
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
        if ($this->onWindows) {
            // Windows treats this symlink as a directory
            rmdir(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2'));
        } else {
            unlink(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2'));
        }
    }

    public function testRelativeSymlinks()
    {
        if ($this->onWindows) {
            $this->markTestSkipped('Relative symlinks do not work in Windows');
        }

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

    public function testWithinDirectory()
    {
        $dir = dirname(__DIR__) . '/fixtures/paths';

        // --- Succeeding paths

        // Normal paths
        $this->assertTrue(PathResolver::within($dir . '/dir1/subdir1/file1', $dir));
        $this->assertTrue(PathResolver::within($dir . '/dir1/subdir1/missing', $dir));
        $this->assertTrue(PathResolver::within($dir . '/dir1/subdir1', $dir));
        $this->assertTrue(PathResolver::within($dir . '/dir1', $dir));
        $this->assertTrue(PathResolver::within($dir, $dir));
        $this->assertTrue(PathResolver::within($dir . '/./', $dir));
        $this->assertTrue(PathResolver::within($dir . '/../paths', $dir));
        // Symlinks
        $this->assertTrue(PathResolver::within($dir . '/dir3/link3', $dir));

        // --- Failing paths

        // Normal paths
        $this->assertFalse(PathResolver::within('./', $dir));
        $this->assertFalse(PathResolver::within($dir . '/../', $dir));
        $this->assertFalse(PathResolver::within($dir . '/../parse', $dir));
        $this->assertFalse(PathResolver::within($dir . '/dir1/subdir1/missing/../../../../', $dir));

        // Symlinks
        $this->assertFalse(PathResolver::within($dir . '/dir3/link3/../../../', $dir));

        // Create an absolute symlink to the fixtures directory
        if (file_exists(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2'))) {
            if ($this->onWindows) {
                // Windows treats this symlink as a directory
                rmdir(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2'));
            } else {
                unlink(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2'));
            }
        }
        symlink(
            str_replace('/', DIRECTORY_SEPARATOR, dirname(__DIR__) . '/fixtures'),
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2')
        );

        $this->assertFalse(PathResolver::within($dir . '/dir2/link2', $dir));
        $this->assertTrue(PathResolver::within($dir . '/dir2/link2/paths', $dir));

        // Remove test symlink
        if ($this->onWindows) {
            // Windows treats this symlink as a directory
            rmdir(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2'));
        } else {
            unlink(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2'));
        }
    }

    public function testWithOpenBaseDirRestrictions()
    {
        $this->markTestSkipped(
            'This test is skipped because it applies open_basedir restrictions which prevent it'
            . ' from cleaning up after itself. You can manually run this test by removing this'
            . ' call and the return underneath it.'
        );
        return;

        $dir = dirname(__DIR__) . '/fixtures/paths';
        $outsideDir = dirname(dirname(dirname(__DIR__)));

        // Create an absolute symlink to an outside location
        if (file_exists(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link3'))) {
            if ($this->onWindows) {
                // Windows treats this symlink as a directory
                rmdir(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link3'));
            } else {
                unlink(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link3'));
            }
        }
        symlink(
            str_replace('/', DIRECTORY_SEPARATOR, $outsideDir),
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link3')
        );

        // Apply a base directory restriction
        ini_set('open_basedir', dirname(dirname(__DIR__)));

        // Check a normal location
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1'),
            resolve_path($dir . '/dir1')
        );

        // Check a valid, but missing, location
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/subdir1/missing'),
            resolve_path($dir . '/dir1/subdir1/missing')
        );

        // Create an absolute symlink to a valid location
        if (file_exists(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2'))) {
            if ($this->onWindows) {
                // Windows treats this symlink as a directory
                rmdir(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2'));
            } else {
                unlink(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2'));
            }
        }
        symlink(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/subdir1'),
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2')
        );

        // Check an absolute symlink to a valid location
        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir1/subdir1'),
            resolve_path($dir . '/dir2/link2')
        );

        // Remove test symlink
        if ($this->onWindows) {
            // Windows treats this symlink as a directory
            rmdir(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2'));
        } else {
            unlink(str_replace('/', DIRECTORY_SEPARATOR, $dir . '/dir2/link2'));
        }

        // Check an outside location fails
        $this->assertFalse(resolve_path($outsideDir));

        // Check an absolute symlink to an outside location fails
        $this->assertFalse(resolve_path($dir . '/dir2/link3'));
    }
}
