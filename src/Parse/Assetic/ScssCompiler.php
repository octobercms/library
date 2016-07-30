<?php namespace October\Rain\Parse\Assetic;

use Event;
use Assetic\Asset\AssetInterface;
use Assetic\Factory\AssetFactory;
use Assetic\Filter\ScssphpFilter;
use Assetic\Filter\HashableInterface;
use Assetic\Filter\FilterInterface;
use Cms\Classes\Theme;

/**
 * Less.php Compiler Filter
 * Class used to compiled stylesheet less files, not using leafo!
 *
 * @package october/parse
 * @author Alexey Bobkov, Samuel Georges
 */
class ScssCompiler extends ScssphpFilter implements HashableInterface
{
    protected $currentFiles = [];

    protected $variables = [];

    public function __construct(){
        Event::listen('cms.combiner.beforePrepare', function($compiler, $assets) {
            foreach ($assets as $asset) {
                if(pathinfo($asset)['extension'] == 'scss'){
                    $this->currentFiles[] = $asset;
                }
            }
        });
    }

    public function setPresets(array $presets)
    {
        $this->variables = array_merge($this->variables, $presets);
    }

    public function setVariables(array $variables)
    {
        $this->variables = array_merge($this->variables, $variables);
    }

    public function addVariable($variable)
    {
        $this->variables[] = $variable;
    }

    public function filterLoad(AssetInterface $asset){
        parent::setVariables($this->variables);
        parent::filterLoad($asset);
    }

    /**
     * Generates a hash for the object
     *
     * @return string Object hash
     */
    public function hash(){
        $themePath = themes_path(Theme::getActiveTheme()->getDirName());
        $factory = new AssetFactory($themePath);

        $allFiles = [];

        foreach ($this->currentFiles as $file) {
           $children = $this->getChildren($factory, file_get_contents($themePath.'/'.$file), $themePath.'/'.dirname($file));
           foreach ($children as $child) {
               $allFiles[] = $child;
           }
        }

        $modifieds = [];

        foreach ($allFiles as $file) {
           $modifieds[] = $file->getLastModified();
        }

        return md5(implode('|', $modifieds));
    }
}
