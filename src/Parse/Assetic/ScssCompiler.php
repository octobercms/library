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

    public function __construct(){
        Event::listen('cms.combiner.beforePrepare', function($compiler, $assets) {
            foreach ($assets as $asset) {
                if(pathinfo($asset)['extension'] == 'scss'){
                    $this->currentFiles[] = $asset;
                }
            }
        });
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
