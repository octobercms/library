<?php namespace October\Rain\Combine;

use File;
use RuntimeException;
use Exception;

/**
 * JsCompile compiles LESS
 *
 * @package october/combine
 * @author Alexey Bobkov, Samuel Georges
 */
class JsCompile
{
    /**
     * @var string basePath is the location of where the processed JS script resides.
     */
    protected $basePath;

    /**
     * @var array includedFiles is a cache of required files.
     */
    protected $includedFiles = [];

    /**
     * compile
     */
    public function compile($js, $options = [])
    {
        extract(array_merge([
            'basePath' => null
        ], $options));

        if (!$basePath) {
            throw new Exception('You must specify a base path');
        }

        $this->basePath = $basePath;
        $this->includedFiles = [];

        return $this->parse($js);
    }

    /**
     * compileFile
     */
    public function compileFile($path, $options = [])
    {
        return $this->compile(file_get_contents($path), $options);
    }

    /**
     * Process JS imports inside a string of javascript
     * @param $content string JS code to process.
     * @return string Processed JS.
     */
    protected function parse($content)
    {
        $imported = '';

        // Look for: /* comments */
        if (!preg_match_all('@/\*(.*)\*/@msU', $content, $matches)) {
            return $content;
        }

        foreach ($matches[1] as $macro) {
            // Look for: =include something
            if (!preg_match_all('/=([^\\s]*)\\s(.*)\n/', $macro, $matches2)) {
                continue;
            }

            foreach ($matches2[1] as $index => $macroName) {
                $method = 'directive' . ucfirst(strtolower($macroName));

                if (method_exists($this, $method)) {
                    $imported .= $this->$method($matches2[2][$index]);
                }
            }
        }

        return $imported . $content;
    }

    /**
     * directiveInclude to process script includes
     */
    protected function directiveInclude($data, $required = false)
    {
        $require = explode(',', $data);
        $result = "";

        foreach ($require as $script) {
            $script = trim($script);

            if (!File::extension($script)) {
                $script = $script . '.js';
            }

            $scriptPath = realpath($this->basePath . '/' . $script);
            if (!File::isFile($scriptPath)) {
                $errorMsg = sprintf("File '%s' not found.", $script);
                if ($required) {
                    throw new RuntimeException($errorMsg);
                }

                $result .= '/* ' . $errorMsg . ' */' . PHP_EOL;
                continue;
            }

            // Exclude duplicates
            if (in_array($script, $this->includedFiles)) {
                continue;
            }

            $this->includedFiles[] = $script;

            // Nested parsing
            $oldScriptPath = $this->basePath;
            $this->basePath = dirname($scriptPath);
            $content = File::get($scriptPath);
            $content = $this->parse($content) . PHP_EOL;
            $this->basePath = $oldScriptPath;

            // Parse in "magic constants"
            $content = str_replace(
                ['__DATE__', '__FILE__'],
                [date("D M j G:i:s T Y"), $script],
                $content
            );

            $result .= $content;
        }

        return $result;
    }

    /**
     * directiveRequire to process mandatory script includes
     */
    protected function directiveRequire($data)
    {
        return $this->directiveInclude($data, true);
    }

    /**
     * directiveDefine to define and replace variables
     */
    protected function directiveDefine($data)
    {
        if (preg_match('@([^\\s]*)\\s+(.*)@', $data, $matches)) {
            // str_replace($matches[1], $matches[2], $context);
            $this->definedVars[] = [$matches[1], $matches[2]];
        }

        return '';
    }
}
