<?php namespace October\Rain\Combine;

use Less_Parser;

/**
 * LessCompile compiles LESS
 *
 * @package october/combine
 * @author Alexey Bobkov, Samuel Georges
 */
class LessCompile
{
    /**
     * compile
     */
    public function compile($less, $options = [])
    {
        extract(array_merge([
            'vars' => null,
            'compress' => false,
        ], $options));

        $parser = new Less_Parser([
            'compress' => (bool) $compress
        ]);

        $parser->parse($less);

        // Set the LESS variables after parsing to override them
        if ($vars) {
            $parser->ModifyVars($vars);
        }

        return $parser->getCss();
    }

    /**
     * compileFile
     */
    public function compileFile($path, $options = [])
    {
        return $this->compile(file_get_contents($path), $options);
    }
}
