<?php namespace October\Rain\Filesystem;

/**
 * A utility to resolve paths to their canonical location and handle path queries.
 *
 * @package october\filesystem
 * @author Ben Thomson
 */
class PathResolver
{
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
    public static function resolve($path)
    {
        // Split path into segments
        $pathSegments = explode('/', static::normalisePath($path));

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

            $currentPath = ($drive ?? '')
                . '/'
                . ((count($resolvedSegments))
                    ? implode('/', $resolvedSegments) . '/'
                    : '')
                . $segment;
            
            if (is_link($currentPath)) {
                // Resolve the symlink and replace the resolved segments with the symlink's segments
                $resolvedSymlink = static::resolveSymlink($currentPath);
                if (!$resolvedSymlink) {
                    return false;
                }

                $resolvedSegments = explode('/', $resolvedSymlink);
                $drive = array_shift($resolvedSegments) ?: null;
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
     * Determines if the path is within the given directory.
     *
     * @param string $path
     * @param string $directory
     * @return bool
     */
    public static function within($path, $directory)
    {
        $directory = static::resolve($directory);
        $path = static::resolve($path);

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
    protected static function normalisePath($path)
    {
        // Change directory separators to Unix-based
        $path = str_replace('\\', '/', $path);

        // Determine drive letter for Windows paths
        $drive = (preg_match('/^([A-Z]:)/', $path, $matches) === 1)
            ? $matches[1]
            : null;

        // Prepend current working directory for relative paths
        if (substr($path, 0, 1) !== '/' && is_null($drive)) {
            $path = static::normalisePath(getcwd()) . '/' . $path;
        }

        return $path;
    }

    /**
     * Resolves a symlink target.
     *
     * @param mixed $path The symlink source's path.
     * @return string|bool
     */
    protected static function resolveSymlink($symlink)
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
            $target = static::resolve($directory . $target);
        }

        return static::normalisePath($target);
    }
}
