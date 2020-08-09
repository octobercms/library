<?php namespace October\Rain\Assetic\Factory\Worker;

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2014 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use October\Rain\Assetic\Asset\AssetInterface;
use October\Rain\Assetic\Factory\AssetFactory;

/**
 * Assets are passed through factory workers before leaving the factory.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
interface WorkerInterface
{
    /**
     * Processes an asset.
     *
     * @param AssetInterface $asset   An asset
     * @param AssetFactory   $factory The factory
     *
     * @return AssetInterface|null May optionally return a replacement asset
     */
    public function process(AssetInterface $asset, AssetFactory $factory);
}
