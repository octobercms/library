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
        $text = <<<HTML
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

    /**
     * testParseHtml
     */
    public function testParseHtml()
    {
        $parser = new Markdown;

        // Check Markdown escaping
        $text = <<<HTML
<div>
    This **text** won't be parsed by *Markdown*
</div>
HTML;

        $normal = $parser->parse($text);

        // Normalize values
        $text = str_replace(["\r", "\n"], '', $text);
        $normal = str_replace(["\r", "\n"], '', $normal);

        $this->assertEquals(nl2br($text), nl2br($normal));

        // Only accepting one node per line
        $text = '<p>Foo</p><p>Bar</p>';
        $normal = $parser->parse($text);
        $this->assertEquals("<p>Foo</p>", $normal);

        // Wrapped as per docs
        $text = '<div><p>Foo</p><p>Bar</p></div>';
        $normal = $parser->parse($text);
        $this->assertEquals("<div><p>Foo</p><p>Bar</p></div>", $normal);
    }
}
