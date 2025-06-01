<?php namespace October\Rain\Composer;

use Exception;
use Throwable;

/**
 * ClassLoader is a custom autoloader used by October CMS, it uses folder names
 * to be lower case and the file name to be capitalized as per the class name.
 */
class ClassLoader
{
    /**
     * @var static|null loader instance
     */
    private static $loader = null;

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
     * @var array unknownClasses cache
     */
    protected $unknownClasses = [];

    /**
     * @var bool manifestDirty if manifest needs to be written
     */
    protected $manifestDirty = false;

    /**
     * @var array namespaces registered
     */
    protected $namespaces = [];

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
    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * instance returns the class loader instance
     */
    public static function instance(): ?static
    {
        return static::$loader;
    }

    /**
     * configure the loader
     */
    public static function configure($basePath)
    {
        return static::$loader = new static($basePath);
    }

    /**
     * withNamespace
     */
    public function withNamespace($namespace, $directory): static
    {
        $this->namespaces[$namespace] = $directory;

        return $this;
    }

    /**
     * withDirectories to the class loader
     * @param string|array $directories
     */
    public function withDirectories($directories): static
    {
        $this->directories = array_merge($this->directories, (array) $directories);

        $this->directories = array_unique($this->directories);

        return $this;
    }

    /**
     * load the given class file
     * @param string $class
     */
    public function load($class): bool
    {
        if (!str_contains($class, '\\')) {
            return false;
        }

        if (
            isset($this->manifest[$class]) &&
            is_file($fullPath = $this->basePath.DIRECTORY_SEPARATOR.$this->manifest[$class])
        ) {
            require $fullPath;
            return true;
        }

        if (isset($this->unknownClasses[$class])) {
            return false;
        }

        [$lowerClass, $upperClass] = $this->normalizeClass($class);

        // Load namespaces
        foreach ($this->namespaces as $namespace => $directory) {
            if (substr($class, 0, strlen($namespace)) === $namespace) {
                if ($this->loadUpperOrLower($class, $directory, $upperClass, $lowerClass) === true) {
                    return true;
                }
            }
        }

        // Load directories
        foreach ($this->directories as $directory) {
            if ($this->loadUpperOrLower($class, $directory, $upperClass, $lowerClass) === true) {
                return true;
            }
        }

        $this->unknownClasses[$class] = true;

        return false;
    }

    /**
     * register the given class loader on the auto-loader stack
     */
    public function register()
    {
        if ($this->registered) {
            return;
        }

        $this->registered = spl_autoload_register(function($class) {
            $this->load($class);
        });
    }

    /**
     * build the manifest and write it to disk
     */
    public function build()
    {
        if (!$this->manifestDirty) {
            return;
        }

        $this->write($this->manifest);
    }

    /**
     * initManifest starts the manifest cache file after registration.
     */
    public function initManifest(string $manifestPath)
    {
        $this->manifestPath = $manifestPath;

        $this->ensureManifestIsLoaded();
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
     * loadUpperOrLower loads a class in a directory with the supplied upper and lower class path.
     */
    protected function loadUpperOrLower(string $class, string $directory, string $upperClass, string $lowerClass): bool
    {
        if ($directory) {
            $directory .= DIRECTORY_SEPARATOR;
        }

        if ($this->isRealFilePath($path = $directory.$lowerClass)) {
            $this->includeClass($class, $path);
            return true;
        }

        if ($this->isRealFilePath($path = $directory.$upperClass)) {
            $this->includeClass($class, $path);
            return true;
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
    protected function includeClass(string $class, string $path)
    {
        require $this->basePath.DIRECTORY_SEPARATOR.$path;

        // Normalize path
        $this->manifest[$class] = str_replace('\\', '/', $path);

        $this->manifestDirty = true;
    }

    /**
     * normalizeClass get the normal file name for a class
     */
    protected function normalizeClass(string $class): array
    {
        // Strip first slash
        if ($class[0] === '\\') {
            $class = substr($class, 1);
        }

        // Lowercase folders
        $parts = explode('\\', $class);
        $file = array_pop($parts);
        $namespace = implode('\\', $parts);
        $directory = str_replace(['\\', '_'], DIRECTORY_SEPARATOR, $namespace);

        // Provide both alternatives
        $lowerClass = strtolower($directory) . DIRECTORY_SEPARATOR . $file . '.php';
        $upperClass = $directory . DIRECTORY_SEPARATOR . $file . '.php';

        return [$lowerClass, $upperClass];
    }

    /**
     * ensureManifestIsLoaded has been loaded into memory
     */
    protected function ensureManifestIsLoaded()
    {
        $manifest = [];

        if (file_exists($this->manifestPath)) {
            try {
                $manifest = require $this->manifestPath;

                if (!is_array($manifest)) {
                    $manifest = [];
                }
            }
            catch (Throwable $ex) {}
        }

        $this->manifest += $manifest;
    }

    /**
     * write the given manifest array to disk
     */
    protected function write(array $manifest)
    {
        if ($this->manifestPath === null) {
            return;
        }

        if (!is_writable(dirname($this->manifestPath))) {
            throw new Exception("The directory [{$this->manifestPath}] must be present and writable.");
        }

        file_put_contents(
            $this->manifestPath,
            '<?php return '.var_export($manifest, true).';'
        );
    }
}
