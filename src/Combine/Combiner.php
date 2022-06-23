<?php namespace October\Rain\Combine;

/**
 * Combiner helper class
 *
 * @package october/combine
 * @author Alexey Bobkov, Samuel Georges
 */
class Combiner
{
    /**
     * minifyCss
     */
    public function minifyCss(string $text)
    {
        return (new StylesheetMinify)->minify($text);
    }

    /**
     * compileLess
     */
    public function compileLess(array $text, $options = [])
    {
        return (new LessCompile)->compile($text, $options);
    }

    /**
     * compileScss
     */
    public function compileScss(array $text, $options = [])
    {
        return (new ScssCompile)->compile($text, $options);
    }

    /**
     * minifyJs
     */
    public function minifyJs(array $text, $options = [])
    {
        return (new JavascriptMinify)->minify($text);
    }

    /**
     * compileJs
     */
    public function compileJs(array $text, $options = [])
    {
        return (new JsCompile)->compile($text, $options);
    }
}
