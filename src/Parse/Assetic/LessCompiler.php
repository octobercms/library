<?php namespace October\Rain\Parse\Assetic;

use Less_Parser;
use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;

/**
 * Less.php Compiler Filter
 * Class used to compiled stylesheet less files, not using leafo!
 *
 * @package october/parse
 * @author Alexey Bobkov, Samuel Georges
 */
class LessCompiler implements FilterInterface
{
    protected $presets = [];

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

}
