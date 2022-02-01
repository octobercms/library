<?php namespace October\Rain\Process;

use Composer\Semver\VersionParser;

/**
 * ComposerPhp handles the composer process functions purely in PHP
 *
 * @package october\process
 * @author Alexey Bobkov, Samuel Georges
 */
class ComposerPhp extends ProcessBase
{
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
     * listPackagesInternal returns a list of installed packages
     */
    protected function listPackagesInternal($useDirect = true)
    {
        $composerLock = base_path('vendor/composer/installed.json');
        $composerFile = base_path('composer.json');

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
