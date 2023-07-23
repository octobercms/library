<?php namespace October\Rain\Combine;

use JSMin;

/**
 * JavascriptMinify minifies JS
 *
 * @package october/combine
 * @author Alexey Bobkov, Samuel Georges
 */
class JavascriptMinify
{
    /**
     * minify CSS
     * @var $css string CSS code to minify.
     * @return string Minified CSS.
     */
    public function minify($css)
    {
        return JSMin::minify($css);
    }

    /**
     * minifyFile
     */
    public function minifyFile($path)
    {
        return $this->minify(file_get_contents($path));
    }
}
