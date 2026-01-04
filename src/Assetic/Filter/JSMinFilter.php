<?php namespace October\Rain\Assetic\Filter;


use JSMin;
use October\Rain\Assetic\Asset\AssetInterface;

/**
 * JSMinFilter filters assets through JsMin.
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
    public function filterLoad(AssetInterface $asset): void
    {
    }

    /**
     * filterDump will use JSMin to minify the asset and checks the filename
     * for "min.js" to issues arising from double minification.
     */
    public function filterDump(AssetInterface $asset): void
    {
        $contents = $asset->getContent();

        $isMinifiedAlready = strpos($asset->getSourcePath(), '.min.js') !== false;
        if (!$isMinifiedAlready) {
            $contents = JSMin::minify($contents);
        }

        $asset->setContent($contents);
    }
}
