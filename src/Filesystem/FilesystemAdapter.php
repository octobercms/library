<?php namespace October\Rain\Filesystem;

use Carbon\Carbon;
use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Rackspace\RackspaceAdapter;
use Illuminate\Filesystem\FilesystemAdapter as BaseFilesystemAdapter;

class FilesystemAdapter extends BaseFilesystemAdapter
{
    /**
     * Get the URL for the file at the given path.
     *
     * @param  string  $path
     * @return string
     *
     * @throws \RuntimeException
     */
    public function url($path)
    {
        $adapter = $this->driver->getAdapter();

        if ($adapter instanceof CachedAdapter) {
            $adapter = $adapter->getAdapter();
        }

        if (method_exists($adapter, 'getUrl')) {
            return $adapter->getUrl($path);
        } elseif (method_exists($this->driver, 'getUrl')) {
            return $this->driver->getUrl($path);
        } elseif ($adapter instanceof AwsS3Adapter) {
            return $this->getAwsUrl($adapter, $path);
        } elseif ($adapter instanceof RackspaceAdapter) {
            return $this->getRackspaceUrl($adapter, $path);
        } elseif ($adapter instanceof Ftp) {
            return $this->getFtpUrl($path);
        } elseif ($adapter instanceof LocalAdapter) {
            return $this->getLocalUrl($path);
        } else {
            throw new RuntimeException('This driver does not support retrieving URLs.');
        }
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param  \League\Flysystem\Rackspace\RackspaceAdapter $adapter
     * @param  string $path
     * @return string
     */
    protected function getRackspaceUrl($adapter, $path)
    {
        return (string) $adapter->getContainer()->getObject($path)->getPublicUrl();
    }

    /**
     * Get a temporary URL for the file at the given path.
     *
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @param  array  $options
     * @return string
     *
     * @throws \RuntimeException
     */
    public function temporaryUrl($path, $expiration, array $options = [])
    {
        $adapter = $this->driver->getAdapter();

        if ($adapter instanceof CachedAdapter) {
            $adapter = $adapter->getAdapter();
        }

        if (method_exists($adapter, 'getTemporaryUrl')) {
            return $adapter->getTemporaryUrl($path, $expiration, $options);
        } elseif ($adapter instanceof AwsS3Adapter) {
            return $this->getAwsTemporaryUrl($adapter, $path, $expiration, $options);
        } elseif ($adapter instanceof RackspaceAdapter) {
            return $this->getRackspaceTemporaryUrl($adapter, $path, $expiration, $options);
        } else {
            throw new RuntimeException('This driver does not support creating temporary URLs.');
        }
    }

    /**
     * Get a temporary URL for the file at the given path.
     *
     * @param  \League\Flysystem\Rackspace\RackspaceAdapter  $adapter
     * @param  string  $path
     * @param  \DateTimeInterface  $expiration
     * @param  array  $options
     * @return string
     */
    public function getRackspaceTemporaryUrl($adapter, $path, $expiration, $options)
    {
        return $adapter->getContainer()->getObject($path)->getTemporaryUrl(
            Carbon::now()->diffInSeconds($expiration),
            $options['method'] ?? 'GET',
            $options['forcePublicUrl'] ?? true
        );
    }
}
