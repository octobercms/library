<?php namespace October\Rain\Assetic\Filter;

use October\Rain\Assetic\Asset\AssetInterface;
use October\Rain\Assetic\Factory\AssetFactory;

/**
 * DependencyExtractorInterface is a filter that knows how to extract dependencies.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
interface DependencyExtractorInterface extends FilterInterface
{
    /**
     * Returns child assets.
     *
     * @param AssetFactory $factory The asset factory
     * @param string $content The asset content
     * @param string $loadPath An optional load path
     *
     * @return AssetInterface[] Child assets
     */
    public function getChildren(AssetFactory $factory, $content, $loadPath = null);
}
