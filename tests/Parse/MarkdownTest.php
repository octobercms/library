<?php

use October\Rain\Parse\Markdown;
use October\Rain\Events\FakeDispatcher;
use October\Rain\Events\Dispatcher;

/**
 * MarkdownTest
 */
class MarkdownTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::swap(new FakeDispatcher(new Dispatcher));
    }

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

        // Multiple nodes per line are preserved
        $text = '<p>Foo</p><p>Bar</p>';
        $normal = $parser->parse($text);
        $this->assertEquals("<p>Foo</p><p>Bar</p>", $normal);

        // Wrapped as per docs
        $text = '<div><p>Foo</p><p>Bar</p></div>';
        $normal = $parser->parse($text);
        $this->assertEquals("<div><p>Foo</p><p>Bar</p></div>", $normal);
    }

    /**
     * testParseHtmlSiblingNodes checks consecutive block elements on a single
     * line, as saved by the rich editor (Froala), are all preserved
     */
    public function testParseHtmlSiblingNodes()
    {
        $parser = new Markdown;

        // Two paragraphs with no whitespace between them
        $text = '<p>First paragraph.</p><p>Second paragraph.</p>';
        $normal = $parser->parse($text);
        $this->assertEquals('<p>First paragraph.</p><p>Second paragraph.</p>', $normal);

        // Three mixed block elements
        $text = '<p>One</p><div>Two</div><p>Three</p>';
        $normal = $parser->parse($text);
        $this->assertEquals('<p>One</p><div>Two</div><p>Three</p>', $normal);

        // Text nodes between elements are kept
        $text = '<p>Foo</p>between<p>Bar</p>';
        $normal = $parser->parse($text);
        $this->assertEquals('<p>Foo</p>between<p>Bar</p>', $normal);

        // Void elements among siblings
        $text = '<p>Foo</p><hr><p>Bar</p>';
        $normal = $parser->parse($text);
        $this->assertEquals('<p>Foo</p><hr><p>Bar</p>', $normal);

        // Attributes are preserved on every sibling
        $text = '<p class="intro" style="color: red">Foo</p><p data-x="1">Bar</p>';
        $normal = $parser->parse($text);
        $this->assertEquals('<p class="intro" style="color: red">Foo</p><p data-x="1">Bar</p>', $normal);

        // Multibyte content survives DOM processing
        $text = '<p>Zażółć gęślą jaźń</p><p>Второй абзац 段落</p>';
        $normal = $parser->parse($text);
        $this->assertEquals('<p>Zażółć gęślą jaźń</p><p>Второй абзац 段落</p>', $normal);
    }

    /**
     * testParseHtmlNestedSiblings checks sibling handling inside wrappers
     * and alongside markdown processing
     */
    public function testParseHtmlNestedSiblings()
    {
        $parser = new Markdown;

        // Nested wrapper followed by a sibling
        $text = '<div><p>Inner one</p><p>Inner two</p></div><p>Outer</p>';
        $normal = $parser->parse($text);
        $this->assertEquals('<div><p>Inner one</p><p>Inner two</p></div><p>Outer</p>', $normal);

        // markdown="1" attribute is processed, sibling is kept
        $text = '<div markdown="1">**bold**</div><p>After</p>';
        $normal = $parser->parse($text);
        $this->assertEquals("<div>\n<p><strong>bold</strong></p>\n</div><p>After</p>", $normal);

        // Markdown content after a multi-node HTML line
        $text = "<p>Foo</p><p>Bar</p>\n\n**Baz**";
        $normal = $parser->parse($text);
        $this->assertEquals("<p>Foo</p><p>Bar</p>\n<p><strong>Baz</strong></p>", $normal);
    }

    /**
     * testParseSafeHtml checks HTML is escaped when using safe mode
     */
    public function testParseSafeHtml()
    {
        $parser = new Markdown;

        $text = '<p>First</p><p>Second</p>';
        $safe = $parser->parseSafe($text);
        $this->assertEquals('<p>&lt;p&gt;First&lt;/p&gt;&lt;p&gt;Second&lt;/p&gt;</p>', $safe);
    }

    public function testParseNonHtml()
    {
        $parser = new Markdown;

        $text = <<<TEXT
<table

some other text

## hello

TEXT;

$expected = '<p>&lt;table</p>
<p>some other text</p>
<h2>hello</h2>';

        $normal = $parser->parse($text);

        // Only accepting one node per line
        $this->assertEquals(str_replace("\r\n", "\n", $expected), $normal);
    }

    public function testParseMultilineHtml()
    {
        $parser = new Markdown;

        $text = <<<HTML
<div>
<table width="100%"
       align="center"
       border="0"
       cellpadding="0"
       cellspacing="0"
       style="background: red; min-height: 500px;">
    <thead>
    <tr>
        <th>Test</th>
        <th>123</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>Lorem</td>
        <td>Ipsum</td>
    </tr>
    </tbody>
</table>
</div>
HTML;

        $expected = <<<HTML
<div>
<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" style="background: red; min-height: 500px;">
    <thead>
    <tr>
        <th>Test</th>
        <th>123</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>Lorem</td>
        <td>Ipsum</td>
    </tr>
    </tbody>
</table>
</div>
HTML;

        $normal = $parser->parse($text);

        $this->assertEquals(str_replace("\r\n", "\n", $expected), $normal);
    }
}
