<?php namespace October\Rain\Support;

use October\Rain\Filesystem\Filesystem;
use Exception;
use Error;

/**
 * ClassLoader is a custom autoloader used by October CMS, it uses folder names
 * to be lower case and the file name to be capitalized as per the class name.
 *
 * @package october\support
 * @author Alexey Bobkov, Samuel Georges
 */
class ClassLoader
{
    /**
     * @var \October\Rain\Filesystem\Filesystem files instance
     */
    public $files;

    /**
     * @var string basePath
     */
    public $basePath;

    /**
     * @var string|null manifestPath
     */
    public $manifestPath;

    /**
     * @var array manifest of loaded items
     */
    public $manifest = [];

    /**
     * @var bool manifestDirty if manifest needs to be written
     */
    protected $manifestDirty = false;

    /**
     * @var array directories registered
     */
    protected $directories = [];

    /**
     * @var bool registered indicates if this class is registered
     */
    protected $registered = false;

    /**
     * __construct creates a new package manifest instance
     */
    public function __construct(Filesystem $files, string $basePath)
    {
        $this->files = $files;
        $this->basePath = $basePath;
    }

    /**
     * load the given class file
     * @param string $class
     */
    public function load($class): bool
    {
        if (
            isset($this->manifest[$class]) &&
            $this->isRealFilePath($path = $this->manifest[$class])
        ) {
            require_once $this->basePath.DIRECTORY_SEPARATOR.$path;
            return true;
        }

        [$lowerClass, $upperClass] = $this->normalizeClass($class);

        foreach ($this->directories as $directory) {
            if ($this->isRealFilePath($path = $directory.DIRECTORY_SEPARATOR.$lowerClass)) {
                $this->includeClass($class, $path);
                return true;
            }

            if ($this->isRealFilePath($path = $directory.DIRECTORY_SEPARATOR.$upperClass)) {
                $this->includeClass($class, $path);
                return true;
            }
        }

        return false;
    }

    /**
     * isRealFilePath determines if a relative path to a file exists and is real
     */
    protected function isRealFilePath(string $path): bool
    {
        return is_file(realpath($this->basePath.DIRECTORY_SEPARATOR.$path));
    }

    /**
     * includeClass and add to the manifest
     */
    protected function includeClass(string $class, string $path): void
    {
        require_once $this->basePath.DIRECTORY_SEPARATOR.$path;

        $this->manifest[$class] = $path;

        $this->manifestDirty = true;
    }

    /**
     * register the given class loader on the auto-loader stack
     */
    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = spl_autoload_register([$this, 'load']);
    }

    /**
     * build the manifest and write it to disk
     */
    public function build(): void
    {
        if (!$this->manifestDirty) {
            return;
        }

        $this->write($this->manifest);
    }

    /**
     * initManifest starts the manifest cache file after registration.
     */
    public function initManifest(string $manifestPath): void
    {
        $this->manifestPath = $manifestPath;

        $this->ensureManifestIsLoaded();
    }

    /**
     * addDirectories to the class loader
     * @param string|array $directories
     */
    public function addDirectories($directories): void
    {
        $this->directories = array_merge($this->directories, (array) $directories);

        $this->directories = array_unique($this->directories);
    }

    /**
     * removeDirectories from the class loader
     * @param string|array $directories
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
        }
    }

    /**
     * getDirectories registered with the loader
     */
    public function getDirectories(): array
    {
        return $this->directories;
    }

    /**
     * normalizeClass get the normal file name for a class
     */
    protected function normalizeClass(string $class): array
    {
        /*
         * Strip first slash
         */
        if ($class[0] === '\\') {
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
     * ensureManifestIsLoaded has been loaded into memory
     */
    protected function ensureManifestIsLoaded(): void
    {
        $manifest = [];

        if (file_exists($this->manifestPath)) {
            try {
                $manifest = $this->files->getRequire($this->manifestPath);

                if (!is_array($manifest)) {
                    $manifest = [];
                }
            }
            catch (Error $ex) {}
        }

        $this->manifest += $manifest;
    }

    /**
     * write the given manifest array to disk
     */
    protected function write(array $manifest): void
    {
        if ($this->manifestPath === null) {
            return;
        }

        if (!is_writable(dirname($this->manifestPath))) {
            throw new Exception('The '.$this->manifestPath.' directory must be present and writable.');
        }

        $this->files->put(
            $this->manifestPath,
            '<?php return '.var_export($manifest, true).';'
        );
    }
}
