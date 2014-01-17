<?php namespace October\Rain\Support\Filters;

use File;
use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;

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
     * @var array Cache of required files.
     */
    private $includedFiles = [];

    public function filterLoad(AssetInterface $asset) {}

    public function filterDump(AssetInterface $asset)
    {
        $this->scriptPath = dirname($asset->getSourceRoot() . '/' . $asset->getSourcePath());
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

                try {
                    $content = $this->$method($matches2[2][$index], $content);
                }
                catch (\Exception $ex) {
                    $content = '/* ' . $ex->getMessage() . ' */';
                }

            }
        }

        return $content;
    }

    /**
     * Directive to process script includes
     */
    private function directiveInclude($data, $context = "", $required = false)
    {
        $require = explode(',', $data);
        $result = "";

        foreach ($require as $script) {
            $script = trim($script);

            if (!File::extension($script))
                $script = $script . '.js';

            $scriptPath = realpath($this->scriptPath . '/' . $script);
            if (!File::isFile($scriptPath)) {
                if ($required)
                    throw new \Exception('Required script does not exist: ' . $script);
                else
                    continue;
            }

            if (in_array($script, $this->includedFiles))
                continue;

            $this->includedFiles[] = $script;

            $content = File::get($scriptPath);
            $content = PHP_EOL . $this->parse($content) . PHP_EOL;

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
    private function directiveRequire($data, $context = "")
    {
        return $this->directiveInclude($data, $context, true);
    }

    /**
     * Directive to define and replace variables
     */
    private function directiveDefine($data, $context = "")
    {
        if (preg_match('@([^\\s]*)\\s+(.*)@', $data, $matches))
            return str_replace($matches[1], $matches[2], $context);
        else
            return $context;
    }

}