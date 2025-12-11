<?php namespace October\Rain\Assetic\Filter;

use October\Rain\Assetic\Asset\AssetInterface;
use October\Rain\Assetic\Factory\AssetFactory;
use October\Rain\Assetic\Filter\LessphpFilter;
use October\Rain\Assetic\Filter\HashableInterface;
use October\Rain\Assetic\Filter\DependencyExtractorInterface;
use October\Rain\Assetic\Filter\FilterInterface;
use Less_Parser;

/**
 * Less.php Compiler Filter
 * Class used to compiled stylesheet less files, not using leafo!
 *
 * @package october/parse
 * @author Alexey Bobkov, Samuel Georges
 */
class LessCompiler implements FilterInterface, HashableInterface, DependencyExtractorInterface
{
    /**
     * @var array presets
     */
    protected $presets = [];

    /**
     * @var string lastHash
     */
    protected $lastHash;

    /**
     * setPresets
     */
    public function setPresets(array $presets)
    {
        $this->presets = $presets;
    }

    /**
     * filterLoad
     */
    public function filterLoad(AssetInterface $asset): void
    {
        $parser = new Less_Parser();

        // CSS Rewriter will take care of this
        $parser->SetOption('relativeUrls', false);

        $parser->parseFile($asset->getSourceRoot() . '/' . $asset->getSourcePath());

        // Set the LESS variables after parsing to override them
        $parser->ModifyVars($this->presets);

        $asset->setContent($parser->getCss());
    }

    /**
     * filterDump
     */
    public function filterDump(AssetInterface $asset): void
    {
    }

    /**
     * hashAsset
     */
    public function hashAsset($asset, $localPath)
    {
        $factory = new AssetFactory($localPath);
        $children = $this->getChildren($factory, file_get_contents($asset), dirname($asset));

        $allFiles = [];
        foreach ($children as $child) {
            $allFiles[] = $child;
        }

        $modified = [];
        foreach ($allFiles as $file) {
            $modified[] = $file->getLastModified();
        }

        return md5(implode('|', $modified));
    }

    /**
     * setHash
     */
    public function setHash($hash)
    {
        $this->lastHash = $hash;
    }

    /**
     * hash generated for the object
     * @return string
     */
    public function hash()
    {
        return $this->lastHash ?: serialize($this);
    }

    /**
     * getChildren loads children recursively
     */
    public function getChildren(AssetFactory $factory, $content, $loadPath = null)
    {
        $children = (new LessphpFilter)->getChildren($factory, $content, $loadPath);

        foreach ($children as $child) {
            $childContent = file_get_contents($child->getSourceRoot().'/'.$child->getSourcePath());
            $children = array_merge($children, (new LessphpFilter)->getChildren($factory, $childContent, $loadPath.'/'.dirname($child->getSourcePath())));
        }

        return $children;
    }
}
