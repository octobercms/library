<?php namespace October\Rain\Support\Filters;

use File;
use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;
use RuntimeException;
use Exception;

/**
 * Importer JS Filter
 * Class used to import referenced javascript files.
 *
 * =include library/jquery.js;
 * =require library/jquery.js;
 * 
 * =define #FOO "Bar";
 * console.log(#FOO);
 *
 * @package october/support
 * @author Alexey Bobkov, Samuel Georges
 */
class JavascriptImporter implements FilterInterface
{

    /**
     * @var string Location of where the processed JS script resides.
     */
    protected $scriptPath;

    /**
     * @var string File name for the processed JS script.
     */
    protected $scriptFile;

    /**
     * @var array Cache of required files.
     */
    protected $includedFiles = [];

    public function filterLoad(AssetInterface $asset) {}

    public function filterDump(AssetInterface $asset)
    {
        $this->scriptPath = dirname($asset->getSourceRoot() . '/' . $asset->getSourcePath());
        $this->scriptFile = basename($asset->getSourcePath());

        $asset->setContent($this->parse($asset->getContent()));
    }

    /**
     * Process JS imports inside a string of javascript
     * @param $content string JS code to process.
     * @return string Processed JS.
     */
    protected function parse($content)
    {
        $macros = [];

        // Look for: /* comments */
        if (!preg_match_all('@/\*(.*)\*/@msU', $content, $matches))
            return $content;

        foreach ($matches[1] as $macro) {

            // Look for: =include something
            if (!preg_match_all('/=([^\\s]*)\\s(.*)\n/', $macro, $matches2))
                continue;

            $matches2[1] = array_reverse($matches2[1]);
            $matches2[2] = array_reverse($matches2[2]);

            foreach ($matches2[1] as $index => $macro_name) {
                $method = 'directive' . ucfirst(strtolower($macro_name));
                if (!method_exists($this, $method))
                    continue;

                $content = $this->$method($matches2[2][$index], $content);
            }
        }

        return $content;
    }

    /**
     * Directive to process script includes
     */
    protected function directiveInclude($data, $context = "", $required = false)
    {
        $require = explode(',', $data);
        $result = "";

        foreach ($require as $script) {
            $script = trim($script);

            if (!File::extension($script)) {
                $script = $script . '.js';
            }

            $scriptPath = realpath($this->scriptPath . '/' . $script);
            if (!File::isFile($scriptPath)) {
                $errorMsg = sprintf("File '%s' not found. in %s", $script, $this->scriptFile);
                if ($required) {
                    throw new RuntimeException($errorMsg);
                }
                else {
                    $result .= PHP_EOL . '/* ' . $errorMsg . ' */' . PHP_EOL;
                    continue;
                }
            }

            /*
             * Exclude duplicates
             */
            if (in_array($script, $this->includedFiles)) {
                continue;
            }

            $this->includedFiles[] = $script;

            /*
             * Nested parsing
             */
            $oldScriptPath = $this->scriptPath;
            $oldScriptFile = $this->scriptFile;

            $this->scriptPath = dirname($scriptPath);
            $this->scriptFile = basename($scriptPath);

            $content = File::get($scriptPath);
            $content = PHP_EOL . $this->parse($content) . PHP_EOL;

            $this->scriptPath = $oldScriptPath;
            $this->scriptFile = $oldScriptFile;

            /*
             * Parse in "magic constants"
             */
            $content = str_replace(
                ['__DATE__', '__FILE__'],
                [date("D M j G:i:s T Y"), $script],
                $content
            );

            $result .= $content;
        }

        return $result . $context;
    }

    /**
     * Directive to process mandatory script includes
     */
    protected function directiveRequire($data, $context = "")
    {
        return $this->directiveInclude($data, $context, true);
    }

    /**
     * Directive to define and replace variables
     */
    protected function directiveDefine($data, $context = "")
    {
        if (preg_match('@([^\\s]*)\\s+(.*)@', $data, $matches))
            return str_replace($matches[1], $matches[2], $context);
        else
            return $context;
    }

}