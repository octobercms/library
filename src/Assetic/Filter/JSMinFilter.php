<?php namespace October\Rain\Assetic\Filter;

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2014 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    public function filterLoad(AssetInterface $asset)
    {
    }

    public function filterDump(AssetInterface $asset)
    {
        // Ignore minify if the assets has .min.js. 
        // 1. Improve performance.
        // 2. Avoid error when minify a minified js.
        if(strpos($asset->getSourcePath(),'.min.js') !== false) {
            $asset->setContent($asset->getContent());
        }else{
            $asset->setContent(\JSMin::minify($asset->getContent()));
        }
    }
}
