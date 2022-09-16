<?php namespace October\Rain\Composer\Concerns;

/**
 * HasAutoloader for composer
 *
 * This trait manages composer packages introduced by plugins. Each loaded
 * package is added to a global pool to ensure a package is not loaded
 * twice by the composer instance introduced by a plugin. This class
 * is used as a substitute for the vendor/autoload.php file.
 *
 * @package october\composer
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasAutoloader
{
    /**
     * @var array namespacePool
     */
    protected $namespacePool = [];

    /**
     * @var array psr4Pool
     */
    protected $psr4Pool = [];

    /**
     * @var array classMapPool
     */
    protected $classMapPool = [];

    /**
     * @var array includeFilesPool
     */
    protected $includeFilesPool = [];

    /**
     * @var Composer\Autoload\ClassLoader loader for the primary composer instance.
     */
    protected $loader;

    /**
     * initAutoloader
     */
    protected function initAutoloader()
    {
        if ($this->loader === null && file_exists($autoLoadFile = base_path('vendor/autoload.php'))) {
            $this->loader = include $autoLoadFile;
            $this->preloadPools();
        }
    }

    /**
     * autoload is a similar function to including vendor/autoload.php.
     * @param string $vendorPath Absoulte path to the vendor directory.
     * @return void
     */
    public function autoload($vendorPath)
    {
        $this->initAutoloader();

        $dir = $vendorPath . '/composer';

        if (file_exists($file = $dir . '/autoload_namespaces.php')) {
            $map = require $file;
            foreach ($map as $namespace => $path) {
                if (isset($this->namespacePool[$namespace])) {
                    continue;
                }
                $this->loader->set($namespace, $path);
                $this->namespacePool[$namespace] = true;
            }
        }

        if (file_exists($file = $dir . '/autoload_psr4.php')) {
            $map = require $file;
            foreach ($map as $namespace => $path) {
                if (isset($this->psr4Pool[$namespace])) {
                    continue;
                }
                $this->loader->setPsr4($namespace, $path);
                $this->psr4Pool[$namespace] = true;
            }
        }

        if (file_exists($file = $dir . '/autoload_classmap.php')) {
            $classMap = require $file;
            if ($classMap) {
                $classMapDiff = array_diff_key($classMap, $this->classMapPool);
                $this->loader->addClassMap($classMapDiff);
                $this->classMapPool += array_fill_keys(array_keys($classMapDiff), true);
            }
        }

        if (file_exists($file = $dir . '/autoload_files.php')) {
            $includeFiles = require $file;
            foreach ($includeFiles as $includeFile) {
                $relativeFile = $this->stripVendorDir($includeFile, $vendorPath);
                if (isset($this->includeFilesPool[$relativeFile])) {
                    continue;
                }
                require $includeFile;
                $this->includeFilesPool[$relativeFile] = true;
            }
        }
    }

    /**
     * preloadPools
     */
    protected function preloadPools()
    {
        $this->classMapPool = array_fill_keys(array_keys($this->loader->getClassMap()), true);
        $this->namespacePool = array_fill_keys(array_keys($this->loader->getPrefixes()), true);
        $this->psr4Pool = array_fill_keys(array_keys($this->loader->getPrefixesPsr4()), true);
        $this->includeFilesPool = $this->preloadIncludeFilesPool();
    }

    /**
     * preloadIncludeFilesPool
     */
    protected function preloadIncludeFilesPool()
    {
        $result = [];
        $vendorPath = base_path() .'/vendor';

        if (file_exists($file = $vendorPath . '/composer/autoload_files.php')) {
            $includeFiles = require $file;
            foreach ($includeFiles as $includeFile) {
                $relativeFile = $this->stripVendorDir($includeFile, $vendorPath);
                $result[$relativeFile] = true;
            }
        }

        return $result;
    }

    /**
     * stripVendorDir removes the vendor directory from a path.
     * @param string $path
     * @return string
     */
    protected function stripVendorDir($path, $vendorDir)
    {
        $path = realpath($path);
        $vendorDir = realpath($vendorDir);

        if (strpos($path, $vendorDir) === 0) {
            $path = substr($path, strlen($vendorDir));
        }

        return $path;
    }
}
