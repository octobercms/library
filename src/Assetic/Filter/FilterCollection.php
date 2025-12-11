<?php namespace October\Rain\Assetic\Filter;

use October\Rain\Assetic\Asset\AssetInterface;
use Traversable;

/**
 * FilterCollection is a collection of filters.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class FilterCollection implements FilterInterface, \IteratorAggregate, \Countable
{
    /**
     * @var array filters
     */
    protected $filters = [];

    /**
     * __construct
     */
    public function __construct(array $filters = [])
    {
        foreach ($filters as $filter) {
            $this->ensure($filter);
        }
    }

    /**
     * Checks that the current collection contains the supplied filter.
     *
     * If the supplied filter is another filter collection, each of its
     * filters will be checked.
     */
    public function ensure(FilterInterface $filter): void
    {
        if ($filter instanceof \Traversable) {
            foreach ($filter as $f) {
                $this->ensure($f);
            }
        } elseif (!in_array($filter, $this->filters, true)) {
            $this->filters[] = $filter;
        }
    }

    /**
     * all
     */
    public function all(): array
    {
        return $this->filters;
    }

    /**
     * clear
     */
    public function clear(): void
    {
        $this->filters = [];
    }

    /**
     * filterLoad
     */
    public function filterLoad(AssetInterface $asset): void
    {
        foreach ($this->filters as $filter) {
            $filter->filterLoad($asset);
        }
    }

    /**
     * filterDump
     */
    public function filterDump(AssetInterface $asset): void
    {
        foreach ($this->filters as $filter) {
            $filter->filterDump($asset);
        }
    }

    /**
     * getIterator
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->filters);
    }

    /**
     * count
     */
    public function count(): int
    {
        return count($this->filters);
    }
}
