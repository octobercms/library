<?php namespace October\Rain\Combine;

use ScssPhp\ScssPhp\Compiler;

/**
 * ScssCompile compiles LESS
 *
 * @package october/combine
 * @author Alexey Bobkov, Samuel Georges
 */
class ScssCompile
{
    /**
     * compile
     */
    public function compile($scss, $options = [])
    {
        extract(array_merge([
            'vars' => null,
            'compress' => false, // @todo
        ], $options));

        $parser = new Compiler();

        if ($vars) {
            $parser->addVariables($vars);
        }

        $result = $parser->compileString($scss);

        return $result->getCss();
    }

    /**
     * compileFile
     */
    public function compileFile($path, $options = [])
    {
        return $this->compile(file_get_contents($path), $options);
    }
}
