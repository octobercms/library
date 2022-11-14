<?php namespace October\Rain\Composer\Concerns;

use Composer\Util\Platform;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use DirectoryIterator;
use RegexIterator;

/**
 * HasAssertions for composer
 *
 * @package october\composer
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasAssertions
{
    /**
     * @var string workingDir
     */
    protected $workingDir;

    /**
     * assertEnvironmentReady
     */
    protected function assertEnvironmentReady()
    {
        // Address resource limits
        @set_time_limit(3600);
        ini_set('max_input_time', 0);
        ini_set('max_execution_time', 0);

        // Function may be disabled for security reasons
        if (!function_exists('putenv')) {
            require_once __DIR__ . '/../resources/putenv.php';
        }
    }

    /**
     * assertHomeVariableSet
     */
    protected function assertHomeVariableSet()
    {
        // Something usable is already set
        $osHome = Platform::isWindows() ? 'APPDATA' : 'HOME';
        if (Platform::getEnv('COMPOSER_HOME') || Platform::getEnv($osHome)) {
            return;
        }

        // Prepare a home location for composer
        $tempPath = temp_path('composer');
        if (!file_exists($tempPath)) {
            @mkdir($tempPath);
        }

        Platform::putEnv('COMPOSER_HOME', $tempPath);
    }

    /**
     * assertHomeDirectory
     */
    protected function assertHomeDirectory()
    {
        $this->workingDir = getcwd();
        chdir(dirname($this->getJsonPath()));
    }

    /**
     * assertWorkingDirectory
     */
    protected function assertWorkingDirectory()
    {
        chdir($this->workingDir);
    }

    /**
     * assertComposerWarmedUp preloads composer in case it wants to update itself
     */
    protected function assertComposerWarmedUp()
    {
        // Preload root package
        $this->assertPackageLoaded('Composer', base_path('vendor/composer/composer/src/Composer'), false);

        // Preload child packages
        $preload = [
            'Composer\Autoload',
            'Composer\Config',
            'Composer\DependencyResolver',
            'Composer\Downloader',
            'Composer\EventDispatcher',
            'Composer\Exception',
            'Composer\Filter',
            'Composer\Installer',
            'Composer\IO',
            'Composer\Json',
            'Composer\Package',
            'Composer\Platform',
            'Composer\Plugin',
            'Composer\Question',
            'Composer\Repository',
            'Composer\Script',
            'Composer\SelfUpdate',
            'Composer\Util',
        ];

        foreach ($preload as $package) {
            $this->assertPackageLoaded(
                $package,
                base_path('vendor/composer/composer/src/'.str_replace("\\", "/", $package))
            );
        }
    }

    /**
     * assertPackageLoaded ensures all classes in a package are loaded
     */
    protected function assertPackageLoaded($packageName, $packagePath, $recursive = true)
    {
        $allFiles = $recursive
            ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($packagePath))
            : new DirectoryIterator($packagePath);

        $phpFiles = new RegexIterator($allFiles, '/\.php$/');
        $packagePathLen = strlen($packagePath);

        foreach ($phpFiles as $phpFile) {
            // Remove base directory and .php extension
            $className = substr($phpFile->getRealPath(), $packagePathLen, -4);

            // Normalize OS path separators, normalize to a class namespace
            $className = trim(str_replace("/", "\\", $className), '\\');

            // Build complete namespace
            $className = $packageName . '\\' . $className;

            // Preload class
            class_exists($className);
        }
    }
}
