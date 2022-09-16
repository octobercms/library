<?php namespace October\Rain\Composer\Concerns;

use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;

/**
 * HasRequirements for composer
 *
 * @package october\composer
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasRequirements
{
    /**
     * @var string composerBackup contents
     */
    protected $composerBackup;

    /**
     * writePackages stores package changes to disk. The requirements key is the package name and the value
     * is the version constraint or false to remove the requirement.
     */
    protected function writePackages(array $requirements)
    {
        $sortPackages = false;
        $isDev = false;
        $requireKey = $isDev ? 'require-dev' : 'require';
        $removeKey = $isDev ? 'require' : 'require-dev';
        $json = new JsonFile($this->getJsonPath());
        $result = null;

        // Update cleanly
        $contents = file_get_contents($json->getPath());
        $manipulator = new JsonManipulator($contents);

        foreach ($requirements as $package => $version) {
            if ($version !== false) {
                $result = $manipulator->addLink($requireKey, $package, $version, $sortPackages);
            }
            else {
                $result = $manipulator->removeSubNode($requireKey, $package);
            }

            if ($result) {
                $result = $manipulator->removeSubNode($removeKey, $package);
            }
        }

        if ($result) {
            $manipulator->removeMainKeyIfEmpty($removeKey);
            file_put_contents($json->getPath(), $manipulator->getContents());
            return;
        }

        // Fallback update
        $composerDefinition = $json->read();
        foreach ($requirements as $package => $version) {
            if ($version !== false) {
                $composerDefinition[$requireKey][$package] = $version;
            }
            else {
                unset($composerDefinition[$requireKey][$package]);
            }

            unset($composerDefinition[$removeKey][$package]);

            if (isset($composerDefinition[$removeKey]) && count($composerDefinition[$removeKey]) === 0) {
                unset($composerDefinition[$removeKey]);
            }
        }

        $json->write($composerDefinition);
    }

    /**
     * restoreComposerFile
     */
    protected function restoreComposerFile()
    {
        if ($this->composerBackup) {
            file_put_contents($this->getJsonPath(), $this->composerBackup);
        }
    }

    /**
     * backupComposerFile
     */
    protected function backupComposerFile()
    {
        $this->composerBackup = file_get_contents($this->getJsonPath());
    }
}
