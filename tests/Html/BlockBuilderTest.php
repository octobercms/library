<?php

use October\Rain\Html\BlockBuilder;

class BlockBuilderTest extends TestCase
{
    public function setUp(): void
    {
        $this->Block = new BlockBuilder();
    }

    public function testPutBlock()
    {
        $this->Block->put('test');

        $this->assertEquals(['test'], $this->Block->getBlockStack());

        echo ''
            . '<div>' . "\n"
            . '    Test' . "\n"
            . '</div>';

        $this->Block->endPut();

        $this->assertEquals(
            ''
            . '<div>' . "\n"
            . '    Test' . "\n"
            . '</div>',
            $this->Block->get('test')
        );

        // Overwrite block
        $this->Block->put('test');

        $this->assertEquals(['test'], $this->Block->getBlockStack());

        echo ''
            . '<div>' . "\n"
            . '    Test2' . "\n"
            . '</div>';

        $this->Block->endPut();

        $this->assertEquals(
            ''
            . '<div>' . "\n"
            . '    Test2' . "\n"
            . '</div>',
            $this->Block->get('test')
        );

        // Append block
        $this->Block->put('test');

        $this->assertEquals(['test'], $this->Block->getBlockStack());

        echo '' . "\n"
            . '<div>' . "\n"
            . '    Test3' . "\n"
            . '</div>';

        $this->Block->endPut(true);

        $this->assertEquals(
            ''
            . '<div>' . "\n"
            . '    Test2' . "\n"
            . '</div>' . "\n"
            . '<div>' . "\n"
            . '    Test3' . "\n"
            . '</div>',
            $this->Block->get('test')
        );
    }

    public function testSetBlock()
    {
        ob_start();

        $this->Block->set('test', 'Inside block');
        echo 'Outside block';

        $content = ob_get_clean();

        $this->assertEquals('Inside block', $this->Block->get('test'));
        $this->assertEquals('Outside block', $content);
    }

    public function testAppendBlock()
    {
        ob_start();

        $this->Block->set('test', 'Inside block');

        echo 'Outside block';

        $this->Block->append('test', ' appended');

        $content = ob_get_clean();

        $this->assertEquals('Inside block appended', $this->Block->get('test'));
        $this->assertEquals('Outside block', $content);
    }

    public function testPlaceholderBlock()
    {
        $this->Block->put('test');

        echo ''
            . '<div>' . "\n"
            . '    Test' . "\n"
            . '</div>';

        $this->Block->endPut();

        $this->assertEquals(
            ''
            . '<div>' . "\n"
            . '    Test' . "\n"
            . '</div>',
            $this->Block->placeholder('test')
        );
        $this->assertNull($this->Block->get('test'));
    }

    public function testResetBlocks()
    {
        $this->Block->put('test');

        echo ''
            . '<div>' . "\n"
            . '    Test' . "\n"
            . '</div>';

        $this->Block->endPut();

        $this->Block->reset();

        $this->assertNull($this->Block->get('test'));
    }

    public function testNestedBlocks()
    {
        ob_start();

        // Start outer block
        $this->Block->put('test');

        echo ''
            . '<div>' . "\n";

        $this->assertEquals(['test'], $this->Block->getBlockStack());

        // Start inner block
        $this->Block->put('inner');

        echo ''
            . '    Test' . "\n";

        $this->assertEquals(['test', 'inner'], $this->Block->getBlockStack());

        // End inner block
        $this->Block->endPut();

        echo ''
            . '</div>';

        $this->assertEquals(['test'], $this->Block->getBlockStack());

        // End outer block
        $this->Block->endPut();

        $content = ob_get_clean();

        $this->assertEmpty($content);
        $this->assertEquals(
            ''
            . '<div>' . "\n"
            . '</div>',
            $this->Block->get('test')
        );
        $this->assertEquals(
            ''
            . '    Test' . "\n",
            $this->Block->get('inner')
        );
    }

    public function testContainBetweenBlocks()
    {
        ob_start();

        $this->Block->put('test');

        echo ''
            . '<div>' . "\n"
            . '    Test' . "\n"
            . '</div>';

        $this->Block->endPut();

        echo 'In between';

        $this->Block->put('test2');

        echo ''
            . '<div>' . "\n"
            . '    Test2' . "\n"
            . '</div>';

        $this->Block->endPut();

        $content = ob_get_clean();

        $this->assertEquals(
            ''
            . '<div>' . "\n"
            . '    Test' . "\n"
            . '</div>',
            $this->Block->get('test')
        );
        $this->assertEquals(
            ''
            . '<div>' . "\n"
            . '    Test2' . "\n"
            . '</div>',
            $this->Block->get('test2')
        );
        $this->assertEquals('In between', $content);
    }
}
