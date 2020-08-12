<?php namespace October\Rain\Filesystem;

use October\Rain\Exception\ApplicationException;

/**
 * A utility to resolve paths to their canonical location and handle path queries.
 *
 * @package october\filesystem
 * @author Ben Thomson
 */
class PathResolver
{
    /** @var string The path to resolve or compare. */
    protected $path;

    /**
     * Constructor.
     *
     * @param string|null $path
     */
    public function __construct($path = null)
    {
        $this->path = $this->normalisePath($path);
    }

    /**
     * Static-based constructor.
     *
     * @param string $path
     * @return static
     */
    public static function with($path)
    {
        return new static($path);
    }

    /**
     * Resolves the path to its canonical location.
     *
     * @return string|bool
     * @throws ApplicationException If the path is not specified
     */
    public function resolve()
    {
        if (empty($this->path)) {
            throw new ApplicationException('You must specify a path to resolve.');
        }

        return $this->resolvePath($this->path);
    }

    /**
     * Determines if the path is within the given directory.
     *
     * @param string $directory
     * @return bool
     * @throws ApplicationException If the path is not specified
     */
    public function within($directory)
    {
        if (empty($this->path)) {
            throw new ApplicationException('You must specify a path to resolve.');
        }

        $directory = $this->resolvePath($this->normalisePath($directory));
        $path = $this->resolve();

        return starts_with($path, $directory);
    }

    /**
     * Normalises a given path.
     *
     * Converts any type of path (Unix or Windows) into a Unix-style path, so that we have a consistent format to work
     * with.
     *
     * @param string $path
     * @return string
     */
    protected function normalisePath($path)
    {
        // Change directory separators to Unix-based
        $path = str_replace('\\', '/', $path);

        // Determine drive letter for Windows paths
        $drive = (preg_match('/^([A-Z]:)/', $path, $matches) === 1)
            ? $matches[1]
            : null;

        // Prepend current working directory for relative paths
        if (substr($path, 0, 1) !== '/' && is_null($drive)) {
            $path = $this->normalisePath(getcwd()) . '/' . $path;
        }

        return $path;
    }

    /**
     * Resolves a path to its canonical location.
     *
     * This expands all symbolic links and resolves references to /./, /../ and extra / characters in the input path
     * and returns the canonicalized absolute pathname.
     *
     * This function operates very similar to the PHP `realpath` function, except it will also work for missing files
     * and directories.
     *
     * Returns canonical path if it can be resolved, otherwise `false`.
     *
     * @param string $path The path to resolve
     * @return string|bool
     */
    protected function resolvePath($path)
    {
        // Split path into segments
        $pathSegments = explode('/', $path);

        // Store Windows drive, if available, for final resolved path.
        $drive = array_shift($pathSegments) ?: null;

        $resolvedSegments = [];

        foreach ($pathSegments as $i => $segment) {
            // Ignore current directory markers or empty segments
            if ($segment === '' || $segment === '.') {
                continue;
            }

            // Traverse back one segment in the resolved segments
            if ($segment === '..' && count($resolvedSegments)) {
                array_pop($resolvedSegments);
                continue;
            }

            $currentPath = '/'
                . ((count($resolvedSegments))
                    ? implode('/', $resolvedSegments) . '/'
                    : '')
                . $segment;
            
            if (is_link($currentPath)) {
                // Resolve the symlink and replace the resolved segments with the symlink's segments
                $resolvedSymlink = $this->resolveSymlink($currentPath);
                if (!$resolvedSymlink) {
                    return false;
                }

                $resolvedSegments = explode('/', $resolvedSymlink);
                continue;
            } elseif (is_file($currentPath) && $i < (count($pathSegments) - 1)) {
                // If we've hit a file and we're trying to relatively traverse the path further, we need to fail at this
                // point.
                return false;
            }

            $resolvedSegments[] = $segment;
        }

        // Generate final resolved path, removing any leftover empty segments
        return
            ($drive ?? '')
            . DIRECTORY_SEPARATOR
            . implode(DIRECTORY_SEPARATOR, array_filter($resolvedSegments, function ($item) {
                return $item !== '';
            }));
    }

    /**
     * Resolves a symlink target.
     *
     * @param mixed $path The symlink source's path.
     * @return string|bool
     */
    protected function resolveSymlink($symlink)
    {
        // Check that the symlink is valid and the target exists
        $stat = linkinfo($symlink);
        if ($stat === -1 || $stat === false) {
            return false;
        }

        $target = readlink($symlink);
        $targetDrive = (preg_match('/^([A-Z]:)/', $symlink, $matches) === 1)
            ? $matches[1]
            : null;

        if (substr($target, 0, 1) !== '/' && is_null($targetDrive)) {
            // Append the target in place of the symlink if it is a relative symlink
            $directory = substr($symlink, 0, strrpos($symlink, '/') + 1);
            $target = $this->resolvePath($directory . $target);
        }

        return $target;
    }
}
