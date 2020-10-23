<?php namespace October\Rain\Filesystem;

use OpenCloud\Rackspace;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Rackspace\RackspaceAdapter;
use Illuminate\Filesystem\FilesystemManager as BaseFilesystemManager;

class FilesystemManager extends BaseFilesystemManager
{
    /**
     * Adapt the filesystem implementation.
     *
     * @param  \League\Flysystem\FilesystemInterface  $filesystem
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected function adapt(FilesystemInterface $filesystem)
    {
        return new FilesystemAdapter($filesystem);
    }

    /**
     * Identify the provided disk and return the name of its config
     *
     * @param \Illuminate\Contracts\Filesystem\Filesystem $disk
     * @return string|null Returns the disk config name if successful, null otherwise.
     */
    public function identify($disk)
    {
        $configName = null;
        foreach ($this->disks as $name => $instantiatedDisk) {
            if ($disk === $instantiatedDisk) {
                $configName = $name;
                break;
            }
        }
        return $configName;
    }

    /**
     * Create an instance of the Rackspace driver.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Filesystem\Cloud
     */
    public function createRackspaceDriver(array $config)
    {
        $client = new Rackspace($config['endpoint'], [
            'username' => $config['username'], 'apiKey' => $config['key'],
        ], $config['options'] ?? []);

        $root = $config['root'] ?? null;

        return $this->adapt($this->createFlysystem(
            new RackspaceAdapter($this->getRackspaceContainer($client, $config), $root),
            $config
        ));
    }

    /**
     * Get the Rackspace Cloud Files container.
     *
     * @param  \OpenCloud\Rackspace  $client
     * @param  array  $config
     * @return \OpenCloud\ObjectStore\Resource\Container
     */
    protected function getRackspaceContainer(Rackspace $client, array $config)
    {
        $urlType = $config['url_type'] ?? null;

        $store = $client->objectStoreService('cloudFiles', $config['region'], $urlType);

        return $store->getContainer($config['container']);
    }
}
