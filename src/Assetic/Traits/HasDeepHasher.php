<?php namespace October\Rain\Assetic\Traits;

use File;
use October\Rain\Assetic\Factory\AssetFactory;

/**
 * HasDeepHasher checks if imports have changed their content and busts the cache
 *
 * @package october/assetic
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasDeepHasher
{
    /**
     * getDeepHashFromCombiner
     */
    public function getDeepHashLastModified($combiner)
    {
        $factory = new AssetFactory($this->localPath);
        return $factory->getLastModified($combiner);
    }

    /**
     * getDeepHashFromAssets returns a deep hash on filters that support it.
     * @param array $assets List of asset files.
     * @return string
     */
    public function getDeepHashFromAssets($assets)
    {
        $key = '';

        $assetFiles = [];
        foreach ($assets as $file) {
            $path = File::symbolizePath($file);
            if (file_exists($path)) {
                $assetFiles[] = $path;
            }
            elseif (file_exists($this->localPath . $path)) {
                $assetFiles[] = $this->localPath . $path;
            }
        }

        foreach ($assetFiles as $file) {
            $filters = $this->getFilters(File::extension($file));

            foreach ($filters as $filter) {
                if (method_exists($filter, 'hashAsset')) {
                    $key .= $filter->hashAsset($file, $this->localPath);
                }
            }
        }

        return $key;
    }

    /**
     * setHashOnCombinerFilters busts the cache based on a different cache key.
     */
    protected function setDeepHashKeyOnFilters($hash)
    {
        $allFilters = array_merge(...array_values($this->getFilters()));

        foreach ($allFilters as $filter) {
            if (method_exists($filter, 'setHash')) {
                $filter->setHash($hash);
            }
        }
    }
}
