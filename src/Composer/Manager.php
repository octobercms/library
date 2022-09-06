<?php namespace October\Rain\Composer;

use Composer\Installer;
use Composer\Composer;
use Composer\Factory;
use Composer\Json\JsonFile;
use Composer\Semver\VersionParser;
use Composer\Config\JsonConfigSource;
use Composer\IO\IOInterface;

/**
 * Manager super class for working with Composer
 *
 * @package october\composer
 * @author Alexey Bobkov, Samuel Georges
 */
class Manager
{
    use \October\Rain\Support\Traits\Singleton;
    use \October\Rain\Composer\HasOutput;
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

        $composer = $this->makeComposer();

        $installer = Installer::create($this->output, $composer);

        try {
            $this->assertHomeDirectory();
            $installer->run();
        }
        finally {
            $this->assertWorkingDirectory();
        }
    }

    /**
     * require runs the "composer require" command
     */
    public function require($package, $version = null)
    {
        // ...
    }

    /**
     * remove runs the "composer remove" command
     */
    public function remove($package)
    {
        // ...
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
     * getJsonPath returns a path to the composer.json file
     */
    protected function getJsonPath(): string
    {
        return base_path('composer.json');
    }

    /**
     * makeComposer returns a new instance of composer
     */
    protected function makeComposer(): Composer
    {
        // Prepare and validate config
        $file = new JsonFile($this->getJsonPath());
        $file->validateSchema(JsonFile::LAX_SCHEMA);
        $config = $file->read();

        // Create composer instance
        $composer = Factory::create($this->output, $config);
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
}
