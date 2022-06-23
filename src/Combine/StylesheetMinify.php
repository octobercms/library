<?php namespace October\Rain\Combine;

/**
 * StylesheetMinify minifies CSS
 *
 * @package october/combine
 * @author Alexey Bobkov, Samuel Georges
 */
class StylesheetMinify
{
    /**
     * minify CSS
     * @var $css string CSS code to minify.
     * @return string Minified CSS.
     */
    public function minify($css)
    {
        // Normalize whitespace in a smart way
        $css = preg_replace('/\s{2,}/', ' ', $css);

        // Remove spaces before and after comment
        $css = preg_replace('/(\s+)(\/\*[^!](.*?)\*\/)(\s+)/', '$2', $css);

        // Remove comment blocks, everything between /* and */, ignore /*! comments
        $css = preg_replace('#/\*[^\!].*?\*/#s', '', $css);

        // Remove ; before }
        $css = preg_replace('/;(?=\s*})/', '', $css);

        // Remove space after , : ; { } */ >, but not after !*/
        $css = preg_replace('/(,|:|;|\{|}|[^!]\*\/|>) /', '$1', $css);

        // Remove space before , ; { } >
        $css = preg_replace('/ (,|;|\{|}|>)/', '$1', $css);

        // Remove newline before } >
        $css = preg_replace('/(\r\n|\r|\n)(})/', '$2', $css);

        // Remove trailing zeros from float numbers preceded by : or a white-space
        // -6.0100em to -6.01em, .0100 to .01, 1.200px to 1.2px
        $css = preg_replace('/((?<!\\\\)\:|\s)(\-?)(\d?\.\d+?)0+([^\d])/S', '$1$2$3$4', $css);

        // Shortern 6-character hex color codes to 3-character where possible
        $css = preg_replace('/#([a-f0-9])\\1([a-f0-9])\\2([a-f0-9])\\3/i', '#\1\2\3', $css);

        return trim($css);
    }

    /**
     * minifyFile
     */
    public function minifyFile($path)
    {
        return $this->minify(file_get_contents($path));
    }
}
