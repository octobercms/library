<?php namespace October\Rain\Composer;

use Config;
use Composer\Factory;
use Composer\Composer;
use Composer\Installer;
use Composer\Json\JsonFile;
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
    use Concerns\HasOutput;
    use Concerns\HasAssertions;
    use Concerns\HasAutoloader;
    use Concerns\HasRequirements;
    use Concerns\HasOctoberCommands;
    use \October\Rain\Support\Traits\Singleton;

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
    public function update(array $packages = [])
    {
        $this->assertEnvironmentReady();
        $this->assertHomeVariableSet();

        try {
            $this->assertHomeDirectory();
            $this->assertComposerWarmedUp();

            Installer::create($this->output, $this->makeComposer())
                ->setDevMode(Config::get('app.debug', false))
                ->setUpdateAllowList($packages)
                ->setPreferDist()
                ->setUpdate(true)
                ->run();
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
        $this->assertEnvironmentReady();
        $this->assertHomeVariableSet();
        $this->backupComposerFile();

        $statusCode = 1;
        $lastException = new Exception('Failed to update composer dependencies');

        try {
            $this->assertHomeDirectory();
            $this->assertComposerWarmedUp();
            $this->writePackages($requirements);

            $composer = $this->makeComposer();
            $installer = Installer::create($this->output, $composer)
                ->setDevMode(Config::get('app.debug', false))
                ->setPreferDist()
                ->setUpdate(true)
                ->setUpdateAllowTransitiveDependencies(Request::UPDATE_LISTED_WITH_TRANSITIVE_DEPS);

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
     * addPackages without update
     */
    public function addPackages(array $requirements)
    {
        $this->writePackages($requirements);
    }

    /**
     * removePackages without update
     */
    public function removePackages(array $packageNames)
    {
        $requirements = [];
        foreach ($packageNames as $package) {
            $requirements[$package] = false;
        }

        $this->writePackages($requirements);
    }

    /**
     * getPackageVersions returns version numbers for the specified packages
     */
    public function getPackageVersions(array $packageNames): array
    {
        $result = [];
        $packages = $this->listAllPackages();

        foreach ($packageNames as $wantPackage) {
            $wantPackageLower = mb_strtolower($wantPackage);

            foreach ($packages as $package) {
                if (!isset($package['name'])) {
                    continue;
                }
                if (mb_strtolower($package['name']) === $wantPackageLower) {
                    $result[$wantPackage] = $package['version'] ?? null;
                }
            }
        }

        return $result;
    }

    /**
     * hasPackage returns true if the specified package is installed
     */
    public function hasPackage($name): bool
    {
        $name = mb_strtolower($name);

        return array_key_exists($name, $this->getPackageVersions([$name]));
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
    public function addRepository($name, $type, $address, $options = [])
    {
        $file = new JsonFile($this->getJsonPath());

        $config = new JsonConfigSource($file);

        $config->addRepository($name, array_merge([
            'type' => $type,
            'url' => $address
        ], $options));
    }

    /**
     * removeRepository will remove a repository from the composer config
     */
    public function removeRepository($name)
    {
        $file = new JsonFile($this->getJsonPath());

        $config = new JsonConfigSource($file);

        $config->removeConfigSetting($name);
    }

    /**
     * hasRepository return true if the composer config contains the repo address
     */
    public function hasRepository($address): bool
    {
        $file = new JsonFile($this->getJsonPath());

        $config = $file->read();

        $repos = $config['repositories'] ?? [];

        foreach ($repos as $repo) {
            if (!isset($repo['url'])) {
                continue;
            }

            if (rtrim($repo['url'], '/') === $address) {
                return true;
            }
        }

        return false;
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
     * getAuthCredentials returns auth credentials added to the config file
     */
    public function getAuthCredentials($hostname, $type = null): ?array
    {
        if ($type === null) {
            $type = 'http-basic';
        }

        $authFile = $this->getAuthPath();

        $config = json_decode(file_get_contents($authFile), true);

        return $config[$type][$hostname] ?? null;
    }

    /**
     * makeComposer returns a new instance of composer
     */
    protected function makeComposer(): Composer
    {
        $composer = Factory::create($this->output);

        // Disable scripts
        $composer->getEventDispatcher()->setRunScripts(false);

        // Discard changes to prevent corrupt state
        $composer->getConfig()->merge([
            'config' => [
                'discard-changes' => true
            ]
        ]);

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
