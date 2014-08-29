<?php namespace October\Rain\Support\Filters;

use Less_Parser;
use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;

/**
 * Less.php Compiler Filter
 * Class used to compiled stylesheet less files, not using leafo!
 *
 * @package october/support
 * @author Alexey Bobkov, Samuel Georges
 */
class LessCompiler implements FilterInterface
{

    public function filterLoad(AssetInterface $asset)
    {
        $parser = new Less_Parser();

        // CSS Rewriter will take care of this
        $parser->SetOption('relativeUrls', false);

        $parser->parseFile($asset->getSourceRoot() . '/' . $asset->getSourcePath());
        $asset->setContent($parser->getCss());
    }

    public function filterDump(AssetInterface $asset)
    {
    }

}