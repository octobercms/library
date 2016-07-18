<?php namespace October\Rain\Parse\Assetic;

use Event;
use Less_Parser;
use Assetic\Asset\AssetInterface;
use Assetic\Factory\AssetFactory;
use Assetic\Filter\LessphpFilter;
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
class LessCompiler implements FilterInterface,HashableInterface
{
    protected $currentFiles = [];
    protected $presets = [];

    public function __construct(){
        Event::listen('cms.combiner.beforePrepare', function($compiler, $assets) {
            foreach ($assets as $asset) {
                if(pathinfo($asset)['extension'] == 'less'){
                    $this->currentFiles[] = $asset;
                }
            }
        });
    }

    public function setPresets(array $presets)
    {
        $this->presets = $presets;
    }

    public function filterLoad(AssetInterface $asset)
    {
        $parser = new Less_Parser();

        // CSS Rewriter will take care of this
        $parser->SetOption('relativeUrls', false);

        $parser->parseFile($asset->getSourceRoot() . '/' . $asset->getSourcePath());

        // Set the LESS variables after parsing to override them
        $parser->ModifyVars($this->presets);

        $asset->setContent($parser->getCss());
    }

    public function filterDump(AssetInterface $asset)
    {
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

    //load children recusive
    public function getChildren(AssetFactory $factory, $content, $loadPath = null){
        $children = LessphpFilter::getChildren($factory, $content, $loadPath);

        foreach ($children as $child) {
            $childContent = file_get_contents($child->getSourceRoot().'/'.$child->getSourcePath());
            $children= array_merge($children, LessphpFilter::getChildren($factory, $childContent, $loadPath.'/'.dirname($child->getSourcePath())));
        }

        return $children;
    }
}
