<?php namespace October\Rain\Assetic\Asset;

use October\Rain\Assetic\Cache\CacheInterface;
use October\Rain\Assetic\Filter\FilterInterface;
use October\Rain\Assetic\Filter\HashableInterface;

/**
 * AssetCache caches an asset to avoid the cost of loading and dumping.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class AssetCache implements AssetInterface
{
    /**
     * @var mixed asset
     */
    protected $asset;

    /**
     * @var mixed cache
     */
    protected $cache;

    /**
     * __construct
     */
    public function __construct(AssetInterface $asset, CacheInterface $cache)
    {
        $this->asset = $asset;
        $this->cache = $cache;
    }

    /**
     * ensureFilter
     */
    public function ensureFilter(FilterInterface $filter)
    {
        $this->asset->ensureFilter($filter);
    }

    /**
     * getFilters
     */
    public function getFilters()
    {
        return $this->asset->getFilters();
    }

    /**
     * clearFilters
     */
    public function clearFilters()
    {
        $this->asset->clearFilters();
    }

    /**
     * load
     */
    public function load(FilterInterface $additionalFilter = null)
    {
        $cacheKey = self::getCacheKey($this->asset, $additionalFilter, 'load');
        if ($this->cache->has($cacheKey)) {
            $this->asset->setContent($this->cache->get($cacheKey));

            return;
        }

        $this->asset->load($additionalFilter);
        $this->cache->set($cacheKey, $this->asset->getContent());
    }

    /**
     * dump
     */
    public function dump(FilterInterface $additionalFilter = null)
    {
        $cacheKey = self::getCacheKey($this->asset, $additionalFilter, 'dump');
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $content = $this->asset->dump($additionalFilter);
        $this->cache->set($cacheKey, $content);

        return $content;
    }

    /**
     * getContent
     */
    public function getContent()
    {
        return $this->asset->getContent();
    }

    /**
     * setContent
     */
    public function setContent($content)
    {
        $this->asset->setContent($content);
    }

    /**
     * getSourceRoot
     */
    public function getSourceRoot()
    {
        return $this->asset->getSourceRoot();
    }

    /**
     * getSourcePath
     */
    public function getSourcePath()
    {
        return $this->asset->getSourcePath();
    }

    /**
     * getSourceDirectory
     */
    public function getSourceDirectory()
    {
        return $this->asset->getSourceDirectory();
    }

    /**
     * getTargetPath
     */
    public function getTargetPath()
    {
        return $this->asset->getTargetPath();
    }

    /**
     * setTargetPath
     */
    public function setTargetPath($targetPath)
    {
        $this->asset->setTargetPath($targetPath);
    }

    /**
     * getLastModified
     */
    public function getLastModified()
    {
        return $this->asset->getLastModified();
    }

    /**
     * getVars
     */
    public function getVars()
    {
        return $this->asset->getVars();
    }

    /**
     * setValues
     */
    public function setValues(array $values)
    {
        $this->asset->setValues($values);
    }

    /**
     * getValues
     */
    public function getValues()
    {
        return $this->asset->getValues();
    }

    /**
     * getCacheKey returns a cache key for the current asset.
     * The key is composed of everything but an asset's content:
     *
     *  * source root
     *  * source path
     *  * target url
     *  * last modified
     *  * filters
     *
     * @param AssetInterface  $asset            The asset
     * @param FilterInterface $additionalFilter Any additional filter being applied
     * @param string          $salt             Salt for the key
     *
     * @return string A key for identifying the current asset
     */
    protected static function getCacheKey(AssetInterface $asset, FilterInterface $additionalFilter = null, $salt = '')
    {
        if ($additionalFilter) {
            $asset = clone $asset;
            $asset->ensureFilter($additionalFilter);
        }

        $cacheKey  = $asset->getSourceRoot();
        $cacheKey .= $asset->getSourcePath();
        $cacheKey .= $asset->getTargetPath();
        $cacheKey .= $asset->getLastModified();

        foreach ($asset->getFilters() as $filter) {
            if ($filter instanceof HashableInterface) {
                $cacheKey .= $filter->hash();
            }
            else {
                $cacheKey .= serialize($filter);
            }
        }

        if ($values = $asset->getValues()) {
            asort($values);
            $cacheKey .= serialize($values);
        }

        return md5($cacheKey.$salt);
    }
}
