<?php

use October\Rain\Parse\Assetic\StylesheetMinify;

class StylesheetMinifyTest extends TestCase
{
    public function testUnitRemoval()
    {
        include __DIR__ . '/MockAsset.php';

        $input  = 'body {width: calc(99.9% * 1/1 - 0px); height: 0px;}';
        $output = 'body {width:calc(99.9% * 1/1 - 0px);height:0}';

        $mockAsset = new MockAsset($input);
        $result    = new StylesheetMinify();
        $result->filterDump($mockAsset);

        $this->assertEquals($output, $mockAsset->getContent());
    }
}