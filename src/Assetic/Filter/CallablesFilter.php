<?php namespace October\Rain\Assetic\Filter;

use October\Rain\Assetic\Asset\AssetInterface;
use October\Rain\Assetic\Factory\AssetFactory;

/**
 * CallablesFilter is a filter that wraps callables.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class CallablesFilter implements FilterInterface, DependencyExtractorInterface
{
    private $loader;
    private $dumper;
    private $extractor;

    /**
     * @param callable|null $loader
     * @param callable|null $dumper
     * @param callable|null $extractor
     */
    public function __construct($loader = null, $dumper = null, $extractor = null)
    {
        $this->loader = $loader;
        $this->dumper = $dumper;
        $this->extractor = $extractor;
    }

    public function filterLoad(AssetInterface $asset)
    {
        if (null !== $callable = $this->loader) {
            $callable($asset);
        }
    }

    public function filterDump(AssetInterface $asset)
    {
        if (null !== $callable = $this->dumper) {
            $callable($asset);
        }
    }

    public function getChildren(AssetFactory $factory, $content, $loadPath = null)
    {
        if (null !== $callable = $this->extractor) {
            return $callable($factory, $content, $loadPath);
        }

        return array();
    }
}
