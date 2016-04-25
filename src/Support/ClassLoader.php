<?php namespace October\Rain\Support;

/**
 * Class loader
 *
 * A simple autoloader used by October, it expects the folder names
 * to be lower case and the file name to be capitalized as per the class name.
 */
class ClassLoader
{
    /**
     * The registered directories.
     *
     * @var array
     */
    protected static $directories = [];

    /**
     * Indicates if a ClassLoader has been registered.
     *
     * @var bool
     */
    protected static $registered = false;

    /**
     * Load the given class file.
     *
     * @param  string  $class
     * @return void
     */
    public static function load($class)
    {
        $class = static::normalizeClass($class);

        foreach (static::$directories as $directory) {
            if (is_file($path = realpath($directory.DIRECTORY_SEPARATOR.$class))) {
                require_once $path;

                return true;
            }
        }
    }

    /**
     * Get the normal file name for a class.
     *
     * @param  string  $class
     * @return string
     */
    public static function normalizeClass($class)
    {
        /*
         * Lowercase folders
         */
        $class = explode('\\', $class);
        $file = array_pop($class);
        $class = strtolower(implode('\\', $class)) . '\\' . $file;

        // Strip first slash
        if ($class[0] == '\\') $class = substr($class, 1);

        return str_replace(['\\', '_'], DIRECTORY_SEPARATOR, $class).'.php';
    }

    /**
     * Register the given class loader on the auto-loader stack.
     *
     * @return void
     */
    public static function register()
    {
        if (!static::$registered) {
            static::$registered = spl_autoload_register(['\October\Rain\Support\ClassLoader', 'load']);
        }
    }

    /**
     * Add directories to the class loader.
     *
     * @param  string|array  $directories
     * @return void
     */
    public static function addDirectories($directories)
    {
        static::$directories = array_merge(static::$directories, (array) $directories);

        static::$directories = array_unique(static::$directories);
    }

    /**
     * Remove directories from the class loader.
     *
     * @param  string|array  $directories
     * @return void
     */
    public static function removeDirectories($directories = null)
    {
        if (is_null($directories)) {
            static::$directories = [];
        }
        else {
            $directories = (array) $directories;

            static::$directories = array_filter(static::$directories, function($directory) use ($directories) {
                return !in_array($directory, $directories);
            });
        }
    }

    /**
     * Gets all the directories registered with the loader.
     *
     * @return array
     */
    public static function getDirectories()
    {
        return static::$directories;
    }

}
