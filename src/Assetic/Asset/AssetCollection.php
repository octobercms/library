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
     * @var array assets
     */
    protected $assets;

    /**
     * @var FilterCollection filters
     */
    protected $filters;

    /**
     * @var string|null sourceRoot
     */
    protected $sourceRoot;

    /**
     * @var string|null targetPath
     */
    protected $targetPath;

    /**
     * @var string|null content
     */
    protected $content;

    /**
     * @var SplObjectStorage clones
     */
    protected $clones;

    /**
     * @var array vars
     */
    protected $vars;

    /**
     * @var array values
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
    public function __construct(array $assets = [], array $filters = [], ?string $sourceRoot = null, array $vars = [])
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
    public function all(): array
    {
        return $this->assets;
    }

    /**
     * add
     */
    public function add(AssetInterface $asset): void
    {
        $this->assets[] = $asset;
    }

    /**
     * removeLeaf
     */
    public function removeLeaf(AssetInterface $needle, bool $graceful = false): bool
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
    public function replaceLeaf(AssetInterface $needle, AssetInterface $replacement, bool $graceful = false): bool
    {
        foreach ($this->assets as $i => $asset) {
            $clone = isset($this->clones[$asset]) ? $this->clones[$asset] : null;
            if (in_array($needle, [$asset, $clone], true)) {
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
    public function ensureFilter(FilterInterface $filter): void
    {
        $this->filters->ensure($filter);
    }

    /**
     * getFilters
     */
    public function getFilters(): array
    {
        return $this->filters->all();
    }

    /**
     * clearFilters
     */
    public function clearFilters(): void
    {
        $this->filters->clear();
        $this->clones = new SplObjectStorage();
    }

    /**
     * load
     */
    public function load(?FilterInterface $additionalFilter = null): void
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
    public function dump(?FilterInterface $additionalFilter = null): string
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
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * setContent
     */
    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    /**
     * getSourceRoot
     */
    public function getSourceRoot(): ?string
    {
        return $this->sourceRoot;
    }

    /**
     * getSourcePath
     */
    public function getSourcePath(): ?string
    {
        return null;
    }

    /**
     * getSourceDirectory returns the first available source directory, useful
     * when extracting imports and a singular collection is returned
     */
    public function getSourceDirectory(): ?string
    {
        foreach ($this as $asset) {
            return $asset->getSourceDirectory();
        }

        return null;
    }

    /**
     * getTargetPath
     */
    public function getTargetPath(): ?string
    {
        return $this->targetPath;
    }

    /**
     * setTargetPath
     */
    public function setTargetPath(?string $targetPath): void
    {
        $this->targetPath = $targetPath;
    }

    /**
     * getLastModified returns the highest last-modified value of all assets in the current collection.
     *
     * @return int|null A UNIX timestamp
     */
    public function getLastModified(): ?int
    {
        if (!count($this->assets)) {
            return null;
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
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * setValues
     */
    public function setValues(array $values): void
    {
        $this->values = $values;

        foreach ($this as $asset) {
            $asset->setValues(array_intersect_key($values, array_flip($asset->getVars())));
        }
    }

    /**
     * getValues
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
