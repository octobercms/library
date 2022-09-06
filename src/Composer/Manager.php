<?php namespace October\Rain\Composer;

use Composer\Factory;
use Composer\Composer;
use Composer\Installer;
use Composer\Json\JsonFile;
use Composer\IO\IOInterface;
use Composer\Semver\VersionParser;
use Composer\Config\JsonConfigSource;
use Composer\DependencyResolver\Request;
use Exception;
use Throwable;

/**
 * Manager super class for working with Composer
 *
 * @method static Manager instance()
 *
 * @package october\composer
 * @author Alexey Bobkov, Samuel Georges
 */
class Manager
{
    use \October\Rain\Support\Traits\Singleton;
    use \October\Rain\Composer\HasOutput;
    use \October\Rain\Composer\HasAssertions;
    use \October\Rain\Composer\HasRequirements;

    /**
     * @var IOInterface output
     */
    protected $output;

    /**
     * init singleton
     */
    public function init()
    {
        $this->setOutput();
    }

    /**
     * update runs the "composer update" command
     */
    public function update()
    {
        $this->assertResourceLimits();
        $this->assertHomeVariableSet();

        try {
            $this->assertHomeDirectory();
            $installer = Installer::create($this->output, $this->makeComposer());
            $installer->setUpdate(true);
            $installer->run();
        }
        finally {
            $this->assertWorkingDirectory();
        }
    }

    /**
     * require runs the "composer require" command
     */
    public function require(array $requirements)
    {
        $this->assertResourceLimits();
        $this->assertHomeVariableSet();
        $this->backupComposerFile();

        $statusCode = 1;
        $lastException = new Exception('Failed to update composer dependencies');

        try {
            $this->assertHomeDirectory();
            $this->writePackages($requirements);

            $composer = $this->makeComposer();
            $installer = Installer::create($this->output, $composer);
            $installer->setUpdate(true);
            $installer->setUpdateAllowTransitiveDependencies(Request::UPDATE_LISTED_WITH_TRANSITIVE_DEPS);

            // If no lock is present, or the file is brand new, we do not do a
            // partial update as this is not supported by the Installer
            if ($composer->getLocker()->isLocked()) {
                $installer->setUpdateAllowList(array_keys($requirements));
            }

            $statusCode = $installer->run();
        }
        catch (Throwable $ex) {
            $statusCode = 1;
            $lastException = $ex;
        }
        finally {
            $this->assertWorkingDirectory();
        }

        if ($statusCode !== 0) {
            $this->restoreComposerFile();
            throw $lastException;
        }
    }

    /**
     * remove runs the "composer remove" command
     */
    public function remove(array $packageNames)
    {
        $requirements = [];
        foreach ($packageNames as $package) {
            $requirements[$package] = false;
        }

        $this->require($requirements);
    }

    /**
     * listPackages returns a list of directly installed packages
     */
    public function listPackages()
    {
        return $this->listPackagesInternal();
    }

    /**
     * listAllPackages returns a list of installed packages, including dependencies
     */
    public function listAllPackages()
    {
        return $this->listPackagesInternal(false);
    }

    /**
     * addRepository will add a repository to the composer config
     */
    public function addRepository($name, $type, $address)
    {
        $file = new JsonFile($this->getJsonPath());

        $config = new JsonConfigSource($file);

        $config->addRepository($name, [
            'type' => $type,
            'url' => $address
        ]);
    }

    /**
     * addAuthCredentials will add credentials to an auth config file
     */
    public function addAuthCredentials($hostname, $username, $password, $type = null)
    {
        if ($type === null) {
            $type = 'http-basic';
        }

        $file = new JsonFile($this->getAuthPath());

        $config = new JsonConfigSource($file, true);

        $config->addConfigSetting($type.'.'.$hostname, [
            'username' => $username,
            'password' => $password
        ]);
    }

    /**
     * makeComposer returns a new instance of composer
     */
    protected function makeComposer(): Composer
    {
        $composer = Factory::create($this->output);

        // Disable scripts
        // $composer->getEventDispatcher()->setRunScripts(false);

        return $composer;
    }

    /**
     * listPackagesInternal returns a list of installed packages
     */
    protected function listPackagesInternal($useDirect = true)
    {
        $composerLock = base_path('vendor/composer/installed.json');
        $composerFile = $this->getJsonPath();

        $installedPackages = json_decode(file_get_contents($composerLock), true);
        $packages = $installedPackages['packages'] ?? [];

        $filter = [];
        if ($useDirect) {
            $composerPackages = json_decode(file_get_contents($composerFile), true);
            $require = array_merge(
                $composerPackages['require'] ?? [],
                $composerPackages['require-dev'] ?? []
            );

            foreach ($require as $pkg => $ver) {
                $filter[$pkg] = true;
            }
        }

        $result = [];
        foreach ($packages as $package) {
            $name = $package['name'] ?? '';
            if ($useDirect && !isset($filter[$name])) {
                continue;
            }

            $result[] = [
                'name' => $name,
                'version' => $this->normalizeVersion($package['version'] ?? ''),
                'description' => $package['description'] ?? '',
            ];
        }

        return $result;
    }

    /**
     * normalizeVersion
     */
    protected function normalizeVersion($packageVersion)
    {
        $version = (new VersionParser)->normalize($packageVersion);
        $parts = explode('.', $version);

        if (count($parts) === 4 && preg_match('{^0\D?}', $parts[3])) {
            unset($parts[3]);
            $version = implode('.', $parts);
        }

        return $version;
    }

    /**
     * getJsonPath returns a path to the composer.json file
     */
    protected function getJsonPath(): string
    {
        return base_path('composer.json');
    }

    /**
     * getAuthPath returns a path to the auth.json file
     */
    protected function getAuthPath(): string
    {
        return base_path('auth.json');
    }
}
