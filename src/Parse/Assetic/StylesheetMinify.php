<?php namespace October\Rain\Parse\Assetic;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;

/**
 * Minify CSS Filter
 * Class used to compress stylesheet css files.
 *
 * @package october/parse
 * @author Alexey Bobkov, Samuel Georges
 */
class StylesheetMinify implements FilterInterface
{
    public function filterLoad(AssetInterface $asset) {}

    public function filterDump(AssetInterface $asset)
    {
        $asset->setContent($this->minify($asset->getContent()));
    }

    /**
     * Minifies CSS
     * @var $css string CSS code to minify.
     * @return string Minified CSS.
     */
    protected function minify($css)
    {
        // Normalize whitespace in a smart way
        $css = preg_replace('/\s{2,}/', ' ', $css);

        // Remove spaces before and after comment
        $css = preg_replace('/(\s+)(\/\*(.*?)\*\/)(\s+)/', '$2', $css);

        // Remove comment blocks, everything between /* and */
        $css = preg_replace('#/\*.*?\*/#s', '', $css);

        // Remove ; before }
        $css = preg_replace('/;(?=\s*})/', '', $css);

        // Remove space after , : ; { } */ >
        $css = preg_replace('/(,|:|;|\{|}|\*\/|>) /', '$1', $css);

        // Remove space before , ; { } >
        $css = preg_replace('/(,|;|\{|}|>)/', '$1', $css);
        
        // Remove newline before } >
        $css = preg_replace('/(\r\n|\r|\n)(})/', '$2', $css);

        // Remove trailing zeros from float numbers preceded by : or a white-space
        // -6.0100em to -6.01em, .0100 to .01, 1.200px to 1.2px
        $css = preg_replace('/((?<!\\\\)\:|\s)(\-?)(\d?\.\d+?)0+([^\d])/S', '$1$2$3$4', $css);

        // Strips units if value is 0 (converts 0px to 0)
        $css = preg_replace('/(:| )(\.?)0(em|ex|px|in|cm|mm|pt|pc)/i', '${1}0', $css);

        // Shortern 6-character hex color codes to 3-character where possible
        $css = preg_replace('/#([a-f0-9])\\1([a-f0-9])\\2([a-f0-9])\\3/i', '#\1\2\3', $css);

        return trim($css);
    }
}
