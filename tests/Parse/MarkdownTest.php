<?php

use October\Rain\Parse\Markdown;

/**
 * MarkdownTest
 */
class MarkdownTest extends TestCase
{
    /**
     * testParseIndent
     */
    public function testParseIndent()
    {
        $parser = new Markdown;

        // Checking expectation
        $text = <<<'HTML'
            Code block
        HTML;

        $normal = $parser->parse($text);
        $indent = $parser->parseIndent($text);

        $this->assertEquals('<pre><code>Code block</code></pre>', $normal);
        $this->assertEquals("<p>Code block</p>", $indent);

        // Checking a quirk
        $text = "##Hello world\nSome other text";

        $normal = $parser->parse($text);
        $indent = $parser->parseIndent($text);

        $this->assertEquals("<h2>Hello world</h2>\n<p>Some other text</p>", $normal);
        $this->assertEquals("<h2>Hello world</h2>\n<p>Some other text</p>", $indent);
    }
}
