<?php namespace October\Rain\Composer;

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
     * writePackages
     */
    protected function writePackages(array $requirements)
    {
        $sortPackages = false;
        $isDev = false;
        $requireKey = $isDev ? 'require-dev' : 'require';
        $removeKey = $isDev ? 'require' : 'require-dev';
        $json = new JsonFile($this->getJsonPath());

        if (!$this->updateFileCleanly($json, $requirements, $requireKey, $removeKey, $sortPackages)) {
            $composerDefinition = $json->read();
            foreach ($requirements as $package => $version) {
                $composerDefinition[$requireKey][$package] = $version;
                unset($composerDefinition[$removeKey][$package]);
                if (isset($composerDefinition[$removeKey]) && count($composerDefinition[$removeKey]) === 0) {
                    unset($composerDefinition[$removeKey]);
                }
            }
            $json->write($composerDefinition);
        }
    }

    /**
     * updateFileCleanly
     */
    protected function updateFileCleanly(JsonFile $json, array $new, string $requireKey, string $removeKey, bool $sortPackages): bool
    {
        $contents = file_get_contents($json->getPath());

        $manipulator = new JsonManipulator($contents);

        foreach ($new as $package => $constraint) {
            if (!$manipulator->addLink($requireKey, $package, $constraint, $sortPackages)) {
                return false;
            }
            if (!$manipulator->removeSubNode($removeKey, $package)) {
                return false;
            }
        }

        $manipulator->removeMainKeyIfEmpty($removeKey);

        file_put_contents($json->getPath(), $manipulator->getContents());

        return true;
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
