<?php namespace October\Rain\Assetic\Asset\Iterator;

use October\Rain\Assetic\Asset\AssetCollectionInterface;
use RecursiveIterator;
use SplObjectStorage;

/**
 * AssetCollectionIterator iterates over an asset collection.
 *
 * The iterator is responsible for cascading filters and target URL patterns
 * from parent to child assets.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class AssetCollectionIterator implements RecursiveIterator
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
     * @var mixed vars
     */
    protected $vars;

    /**
     * @var mixed output
     */
    protected $output;

    /**
     * @var mixed clones
     */
    protected $clones;

    /**
     * __construct
     */
    public function __construct(AssetCollectionInterface $coll, SplObjectStorage $clones)
    {
        $this->assets  = $coll->all();
        $this->filters = $coll->getFilters();
        $this->vars    = $coll->getVars();
        $this->output  = $coll->getTargetPath();
        $this->clones  = $clones;

        if (false === $pos = strrpos($this->output, '.')) {
            $this->output .= '_*';
        }
        else {
            $this->output = substr($this->output, 0, $pos).'_*'.substr($this->output, $pos);
        }
    }

    /**
     * Returns a copy of the current asset with filters and a target URL applied.
     *
     * @param Boolean $raw Returns the unmodified asset if true
     *
     * @return \October\Rain\Assetic\Asset\AssetInterface
     */
    public function current($raw = false)
    {
        $asset = current($this->assets);

        if ($raw) {
            return $asset;
        }

        // clone once
        if (!isset($this->clones[$asset])) {
            $clone = $this->clones[$asset] = clone $asset;

            // generate a target path based on asset name
            $name = sprintf('%s_%d', pathinfo($asset->getSourcePath(), PATHINFO_FILENAME) ?: 'part', $this->key() + 1);

            $name = $this->removeDuplicateVar($name);

            $clone->setTargetPath(str_replace('*', $name, $this->output));
        } else {
            $clone = $this->clones[$asset];
        }

        // cascade filters
        foreach ($this->filters as $filter) {
            $clone->ensureFilter($filter);
        }

        return $clone;
    }

    public function key()
    {
        return key($this->assets);
    }

    public function next(): void
    {
        next($this->assets);
    }

    public function rewind(): void
    {
        reset($this->assets);
    }

    public function valid(): bool
    {
        return current($this->assets) !== false;
    }

    public function hasChildren(): bool
    {
        return current($this->assets) instanceof AssetCollectionInterface;
    }

    /**
     * @uses current()
     */
    public function getChildren(): ?RecursiveIterator
    {
        return new self($this->current(), $this->clones);
    }

    private function removeDuplicateVar($name)
    {
        foreach ($this->vars as $var) {
            $var = '{'.$var.'}';
            if (false !== strpos($name, $var) && false !== strpos($this->output, $var)) {
                $name = str_replace($var, '', $name);
            }
        }

        return $name;
    }
}
