<?php namespace October\Rain\Support;

use October\Rain\Filesystem\Filesystem;
use Throwable;
use Exception;

/**
 * Class loader
 *
 * A simple autoloader used by October, it expects the folder names
 * to be lower case and the file name to be capitalized as per the class name.
 */
class ClassLoader
{
    /**
     * The filesystem instance.
     *
     * @var \October\Rain\Filesystem\Filesystem
     */
    public $files;

    /**
     * A map of namespaces to directories.
     *
     * @var array
     */
    public $namespaceMap = [];

    /**
     * The base path.
     *
     * @var string
     */
    public $basePath;

    /**
     * The manifest path.
     *
     * @var string|null
     */
    public $manifestPath;

    /**
     * The loaded manifest array.
     *
     * @var array
     */
    public $manifest;

    /**
     * Determine if the manifest needs to be written.
     *
     * @var bool
     */
    protected $manifestDirty = false;

    /**
     * The registered directories.
     *
     * @var array
     */
    protected $directories = [];

    /**
     * Indicates if a ClassLoader has been registered.
     *
     * @var bool
     */
    protected $registered = false;

    /**
     * Create a new package manifest instance.
     *
     * @param  \October\Rain\Filesystem\Filesystem  $files
     * @param  string  $basePath
     * @param  string  $manifestPath
     * @return void
     */
    public function __construct(Filesystem $files, $basePath, $manifestPath)
    {
        $this->files = $files;
        $this->basePath = $basePath;
        $this->manifestPath = $manifestPath;
    }

    /**
     * Load the given class file.
     *
     * @param  string  $class
     * @return bool
     */
    public function load($class)
    {
        if (
            isset($this->manifest[$class]) &&
            $this->isRealFilePath($path = $this->manifest[$class])
        ) {
            require_once $this->basePath.DIRECTORY_SEPARATOR.$path;
            return true;
        }

        foreach ($this->directories as $directory) {
            [$lowerPath, $upperPath] = static::normalizePath($class, $directory);

            if ($this->isRealFilePath($lowerPath)) {
                $this->includeClass($class, $lowerPath);
                return true;
            }

            if ($this->isRealFilePath($upperPath)) {
                $this->includeClass($class, $upperPath);
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a relative path to a file exists and is real
     *
     * @param  string  $path
     * @return bool
     */
    protected function isRealFilePath($path)
    {
        return is_file(realpath($this->basePath.DIRECTORY_SEPARATOR.$path));
    }

    /**
     * Includes a class and adds to the manifest
     *
     * @param  string  $class
     * @param  string  $path
     * @return void
     */
    protected function includeClass($class, $path)
    {
        require_once $this->basePath.DIRECTORY_SEPARATOR.$path;

        $this->manifest[$class] = $path;

        $this->manifestDirty = true;
    }

    /**
     * Register the given class loader on the auto-loader stack.
     *
     * @return void
     */
    public function register()
    {
        if ($this->registered) {
            return;
        }

        $this->ensureManifestIsLoaded();

        $this->registered = spl_autoload_register([$this, 'load']);
    }

    /**
     * Build the manifest and write it to disk.
     *
     * @return void
     */
    public function build()
    {
        if (!$this->manifestDirty) {
            return;
        }

        $this->write($this->manifest);
    }

    /**
     * Add directories to the class loader.
     *
     * Directories can be defined a single directory as a string, an array of directories as strings, or an array with
     * a key/value that represents a path and expected namespace - ie. "tests" => "October\\Core\\Tests\\"
     *
     * @param string|array $directories
     * @return void
     */
    public function addDirectories($directories)
    {
        // Traverse directories and map them to the directory or namespace map arrays as necessary
        if (is_string($directories)) {
            $this->directories[] = $directories;
        } elseif (is_array($directories)) {
            foreach ($directories as $path => $directory) {
                if (is_string($directory)) {
                    $this->directories[] = $directory;
                } elseif (is_string($path)) {
                    $this->directories[] = $path;
                    $this->namespaceMap[$path] = $directory;
                } elseif (is_array($directory)) {
                    $path = key($directory);
                    $directory = $directory[$path];

                    $this->directories[] = $path;
                    $this->namespaceMap[$path] = $directory;
                }
            }
        }

        $this->directories = array_unique($this->directories);
    }

    /**
     * Remove directories from the class loader.
     *
     * @param  string|array  $directories
     * @return void
     */
    public function removeDirectories($directories = null)
    {
        if (is_null($directories)) {
            $this->directories = [];
        }
        else {
            $directories = (array) $directories;

            $this->directories = array_filter($this->directories, function ($directory) use ($directories) {
                return !in_array($directory, $directories);
            });
            $this->namespaceMap = array_filter($this->namespaceMap, function ($directory) use ($directories) {
                return !in_array($directory, $directories);
            }, ARRAY_FILTER_USE_KEY);
        }
    }

    /**
     * Gets all the directories registered with the loader.
     *
     * @return array
     */
    public function getDirectories()
    {
        return $this->directories;
    }

    /**
     * Gets all the namespace maps registered with the loader.
     *
     * @return array
     */
    public function getNamespaceMap()
    {
        return $this->namespaceMap;
    }

    /**
     * Get the normal file name for a class.
     *
     * @param string $class
     * @return array
     */
    protected function normalizeClass($class)
    {
        /*
         * Strip first slash
         */
        if ($class[0] == '\\') {
            $class = substr($class, 1);
        }

        /*
         * Lowercase folders
         */
        $parts = explode('\\', $class);
        $file = array_pop($parts);
        $namespace = implode('\\', $parts);
        $directory = str_replace(['\\', '_'], DIRECTORY_SEPARATOR, $namespace);

        /*
         * Provide both alternatives
         */
        $lowerClass = strtolower($directory) . DIRECTORY_SEPARATOR . $file . '.php';
        $upperClass = $directory . DIRECTORY_SEPARATOR . $file . '.php';

        return [$lowerClass, $upperClass];
    }

    /**
     * Get the normal (expected) file paths for a class based on the directory that's being searched.
     *
     * @param string $class
     * @param string $directory
     * @return array
     */
    protected function normalizePath($class, $directory)
    {
        [$lowerClass, $upperClass] = static::normalizeClass($class);

        // If a namespace is assigned for this directory, map it to a directory structure in lower and normal form.
        $lowerNS = $upperNS = null;

        if (isset($this->namespaceMap[$directory])) {
            $namespace = $this->namespaceMap[$directory];

            // Ensure a trailing slash
            if (substr($namespace, -1) !== '\\') {
                $namespace .= '\\';
            }

            $lowerNS = str_replace(['\\', '_'], DIRECTORY_SEPARATOR, strtolower($namespace));
            $upperNS = str_replace(['\\', '_'], DIRECTORY_SEPARATOR, $namespace);
        }

        // Return expected paths
        $lowerPath = $directory
            . DIRECTORY_SEPARATOR
            . ((isset($lowerNS))
                ? str_replace($lowerNS, '', $lowerClass)
                : $lowerClass);
        $upperPath = $directory
            . DIRECTORY_SEPARATOR
            . ((isset($upperNS))
                ? str_replace($upperNS, '', $upperClass)
                : $upperClass);

        return [$lowerPath, $upperPath];
    }

    /**
     * Ensure the manifest has been loaded into memory.
     *
     * @return void
     */
    protected function ensureManifestIsLoaded()
    {
        if (!is_null($this->manifest)) {
            return;
        }

        if (file_exists($this->manifestPath)) {
            try {
                $this->manifest = $this->files->getRequire($this->manifestPath);

                if (!is_array($this->manifest)) {
                    $this->manifest = [];
                }
            }
            catch (Exception $ex) {
                $this->manifest = [];
            }
            catch (Throwable $ex) {
                $this->manifest = [];
            }
        }
        else {
            $this->manifest = [];
        }
    }

    /**
     * Write the given manifest array to disk.
     *
     * @param  array  $manifest
     * @return void
     * @throws \Exception
     */
    protected function write(array $manifest)
    {
        if (!is_writable(dirname($this->manifestPath))) {
            throw new Exception('The storage/framework/cache directory must be present and writable.');
        }

        $this->files->put(
            $this->manifestPath,
            '<?php return '.var_export($manifest, true).';'
        );
    }
}
