<?php

use October\Rain\Assetic\Filter\StylesheetMinify;

include __DIR__ . '/MockAsset.php';

class StylesheetMinifyTest extends TestCase
{
    public function testSpaceRemoval()
    {
        $input  = 'body {width: calc(99.9% * 1/1 - 0px); height: 0px;}';
        $output = 'body {width:calc(99.9% * 1/1 - 0px);height:0px}';

        $mockAsset = new MockAsset($input);
        $result    = new StylesheetMinify();
        $result->filterDump($mockAsset);

        $this->assertEquals($output, $mockAsset->getContent());
    }

    public function testEmptyClassPreserve()
    {
        $input  = '.view { /*
 * Text
 */
  /*
 * Links
 */
  /*
 * Table
 */
  /*
 * Table cell
 */
  /*
 * Images
 */ }';
        $output = '.view {}';

        $mockAsset = new MockAsset($input);
        $result    = new StylesheetMinify();
        $result->filterDump($mockAsset);

        $this->assertEquals($output, $mockAsset->getContent());
    }

    public function testCommentRemoval()
    {
        $input  = 'body {/* First comment */} /* Second comment */';
        $output = 'body {}';

        $mockAsset = new MockAsset($input);
        $result    = new StylesheetMinify();
        $result->filterDump($mockAsset);

        $this->assertEquals($output, $mockAsset->getContent());
    }

    public function testCommentPreservationInVar()
    {
        $input  = '--ring-inset: var(--empty, /*!*/ /*!*/);';
        $output = '--ring-inset:var(--empty, );';

        $mockAsset = new MockAsset($input);
        $result    = new StylesheetMinify();
        $result->filterDump($mockAsset);

        $this->assertEquals($output, $mockAsset->getContent());
    }

    public function testUnitPreservationInVar()
    {
        $input  = '--offset-width: 0px';
        $output = '--offset-width:0px';

        $mockAsset = new MockAsset($input);
        $result    = new StylesheetMinify();
        $result->filterDump($mockAsset);

        $this->assertEquals($output, $mockAsset->getContent());
    }
}
