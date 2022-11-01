<?php namespace October\Rain\Assetic\Asset;

use October\Rain\Assetic\Asset\Iterator\AssetCollectionFilterIterator;
use October\Rain\Assetic\Asset\Iterator\AssetCollectionIterator;
use October\Rain\Assetic\Filter\FilterCollection;
use October\Rain\Assetic\Filter\FilterInterface;
use RecursiveIteratorIterator;
use InvalidArgumentException;
use IteratorAggregate;
use SplObjectStorage;
use Traversable;

/**
 * AssetCollection
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class AssetCollection implements IteratorAggregate, AssetCollectionInterface
{
    /**
     * @var mixed assets
     */
    protected $assets;

    /**
     * @var mixed filters
     */
    protected $filters;

    /**
     * @var mixed sourceRoot
     */
    protected $sourceRoot;

    /**
     * @var mixed targetPath
     */
    protected $targetPath;

    /**
     * @var mixed content
     */
    protected $content;

    /**
     * @var mixed clones
     */
    protected $clones;

    /**
     * @var mixed vars
     */
    protected $vars;

    /**
     * @var mixed values
     */
    protected $values;

    /**
     * __construct
     *
     * @param array  $assets     Assets for the current collection
     * @param array  $filters    Filters for the current collection
     * @param string $sourceRoot The root directory
     * @param array  $vars
     */
    public function __construct($assets = [], $filters = [], $sourceRoot = null, array $vars = [])
    {
        $this->assets = [];
        foreach ($assets as $asset) {
            $this->add($asset);
        }

        $this->filters = new FilterCollection($filters);
        $this->sourceRoot = $sourceRoot;
        $this->clones = new SplObjectStorage();
        $this->vars = $vars;
        $this->values = [];
    }

    /**
     * __clone
     */
    public function __clone()
    {
        $this->filters = clone $this->filters;
        $this->clones = new SplObjectStorage();
    }

    /**
     * all
     */
    public function all()
    {
        return $this->assets;
    }

    /**
     * add
     */
    public function add(AssetInterface $asset)
    {
        $this->assets[] = $asset;
    }

    /**
     * removeLeaf
     */
    public function removeLeaf(AssetInterface $needle, $graceful = false)
    {
        foreach ($this->assets as $i => $asset) {
            $clone = isset($this->clones[$asset]) ? $this->clones[$asset] : null;
            if (in_array($needle, [$asset, $clone], true)) {
                unset($this->clones[$asset], $this->assets[$i]);

                return true;
            }

            if ($asset instanceof AssetCollectionInterface && $asset->removeLeaf($needle, true)) {
                return true;
            }
        }

        if ($graceful) {
            return false;
        }

        throw new InvalidArgumentException('Leaf not found.');
    }

    /**
     * replaceLeaf
     */
    public function replaceLeaf(AssetInterface $needle, AssetInterface $replacement, $graceful = false)
    {
        foreach ($this->assets as $i => $asset) {
            $clone = isset($this->clones[$asset]) ? $this->clones[$asset] : null;
            if (in_array($needle, array($asset, $clone), true)) {
                unset($this->clones[$asset]);
                $this->assets[$i] = $replacement;

                return true;
            }

            if ($asset instanceof AssetCollectionInterface && $asset->replaceLeaf($needle, $replacement, true)) {
                return true;
            }
        }

        if ($graceful) {
            return false;
        }

        throw new InvalidArgumentException('Leaf not found.');
    }

    /**
     * ensureFilter
     */
    public function ensureFilter(FilterInterface $filter)
    {
        $this->filters->ensure($filter);
    }

    /**
     * getFilters
     */
    public function getFilters()
    {
        return $this->filters->all();
    }

    /**
     * clearFilters
     */
    public function clearFilters()
    {
        $this->filters->clear();
        $this->clones = new SplObjectStorage();
    }

    /**
     * load
     */
    public function load(FilterInterface $additionalFilter = null)
    {
        // loop through leaves and load each asset
        $parts = [];
        foreach ($this as $asset) {
            $asset->load($additionalFilter);
            $parts[] = $asset->getContent();
        }

        $this->content = implode("\n", $parts);
    }

    /**
     * dump
     */
    public function dump(FilterInterface $additionalFilter = null)
    {
        // loop through leaves and dump each asset
        $parts = [];
        foreach ($this as $asset) {
            $parts[] = $asset->dump($additionalFilter);
        }

        return implode("\n", $parts);
    }

    /**
     * getContent
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * setContent
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * getSourceRoot
     */
    public function getSourceRoot()
    {
        return $this->sourceRoot;
    }

    /**
     * getSourcePath
     */
    public function getSourcePath()
    {
    }

    /**
     * getSourceDirectory returns the first available source directory, useful
     * when extracting imports and a singular collection is returned
     */
    public function getSourceDirectory()
    {
        foreach ($this as $asset) {
            return $asset->getSourceDirectory();
        }
    }

    /**
     * getTargetPath
     */
    public function getTargetPath()
    {
        return $this->targetPath;
    }

    /**
     * setTargetPath
     */
    public function setTargetPath($targetPath)
    {
        $this->targetPath = $targetPath;
    }

    /**
     * getLastModified returns the highest last-modified value of all assets in the current collection.
     *
     * @return integer|null A UNIX timestamp
     */
    public function getLastModified()
    {
        if (!count($this->assets)) {
            return;
        }

        $mtime = 0;
        foreach ($this as $asset) {
            $assetMtime = $asset->getLastModified();
            if ($assetMtime > $mtime) {
                $mtime = $assetMtime;
            }
        }

        return $mtime;
    }

    /**
     * getIterator returns an iterator for looping recursively over unique leaves.
     */
    public function getIterator(): Traversable
    {
        return new RecursiveIteratorIterator(new AssetCollectionFilterIterator(new AssetCollectionIterator($this, $this->clones)));
    }

    /**
     * getVars
     */
    public function getVars()
    {
        return $this->vars;
    }

    /**
     * setValues
     */
    public function setValues(array $values)
    {
        $this->values = $values;

        foreach ($this as $asset) {
            $asset->setValues(array_intersect_key($values, array_flip($asset->getVars())));
        }
    }

    /**
     * getValues
     */
    public function getValues()
    {
        return $this->values;
    }
}
