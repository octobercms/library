<?php namespace October\Rain\Assetic\Filter;

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2014 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use JSMin;
use October\Rain\Assetic\Asset\AssetInterface;

/**
 * Filters assets through JsMin.
 *
 * All credit for the filter itself is mentioned in the file itself.
 *
 * @link https://raw.github.com/mrclay/minify/master/min/lib/JSMin.php
 * @author Brunoais <brunoaiss@gmail.com>
 */
class JSMinFilter implements FilterInterface
{
    /**
     * filterLoad
     */
    public function filterLoad(AssetInterface $asset)
    {
    }

    /**
     * filterDump will use JSMin to minify the asset and checks the filename
     * for "min.js" to issues arising from double minification.
     */
    public function filterDump(AssetInterface $asset)
    {
        $contents = $asset->getContent();

        $isMinifiedAlready = strpos($asset->getSourcePath(), '.min.js') !== false;
        if (!$isMinifiedAlready) {
            $contents = JSMin::minify($contents);
        }

        $asset->setContent($contents);
    }
}
