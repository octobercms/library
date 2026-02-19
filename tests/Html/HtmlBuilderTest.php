<?php

use October\Rain\Html\HtmlBuilder;

class HtmlBuilderTest extends TestCase
{
    public function testStrip()
    {
        $result = with(new HtmlBuilder)->strip('<p>hello</p>');
        $this->assertEquals('hello', $result);
    }

    public function testLimit()
    {
        $result = with(new HtmlBuilder)->limit('<p>The quick brown fox jumped over the lazy dog</p>', 10);
        $this->assertEquals('<p>The quick ...</p>', $result);

        $result = with(new HtmlBuilder)->limit("<p>The quick brown fox's jumped over the lazy dog</p>", 25, '!!!');
        $this->assertEquals("<p>The quick brown fox's jum!!!</p>", $result);

        $result = with(new HtmlBuilder)->limit("<p>The quick brown fox jumped over the lazy dog</p><p>The quick brown fox jumped over the lazy dog</p>", 50);
        $this->assertEquals('<p>The quick brown fox jumped over the lazy dog</p><p>The qu...</p>', $result);

        $input = str_replace("\r\n", "\n", trim("
            <p>The quick brown fox jumped over the lazy dog</p>
            <p>The quick brown fox jumped over the lazy dog</p>
        "));
        $result = with(new HtmlBuilder)->limit($input, 60);

        $expected = str_replace("\r\n", "\n", trim('
            <p>The quick brown fox jumped over the lazy dog</p>
            <p>The...</p>
        '));
        $this->assertEquals($expected, $result);
    }

    //
    // clean() tests - HTML sanitization
    //

    public function testCleanRemovesScriptTags()
    {
        $result = HtmlBuilder::clean('<script>window.location = "http://google.com"</script>');
        $this->assertStringNotContainsString('<script', $result);
        $this->assertStringNotContainsString('</script', $result);
    }

    public function testCleanRemovesStyleAttribute()
    {
        // Style attributes are stripped by the sanitizer for security
        $result = HtmlBuilder::clean('<span style="width: expression(alert(\'Ping!\'));"></span>');
        $this->assertStringNotContainsString('expression', $result);
    }

    public function testCleanRemovesJavaScriptProtocol()
    {
        $result = HtmlBuilder::clean('<a href="javascript:alert(\'Ping!\');">Test</a>');
        $this->assertStringNotContainsString('javascript:', $result);

        $result = HtmlBuilder::clean('<a href=" &#14;  javascript: alert(\'Ping!\');">Test</a>');
        $this->assertStringNotContainsString('javascript:', $result);

        $result = HtmlBuilder::clean('<a href=" &#14  javascript: alert(\'Ping!\');">Test</a>');
        $this->assertStringNotContainsString('javascript:', $result);
    }

    public function testCleanRemovesVbScriptProtocol()
    {
        $result = HtmlBuilder::clean('<a href="vbscript:msgbox(\'XSS\')">Test</a>');
        $this->assertStringNotContainsString('vbscript:', $result);

        // With spaces/encoding
        $result = HtmlBuilder::clean('<a href="vb script:msgbox(\'XSS\')">Test</a>');
        $this->assertStringNotContainsString('vbscript', strtolower($result));
    }

    public function testCleanRemovesDataProtocol()
    {
        $result = HtmlBuilder::clean('<a href="data:text/html,<script>alert(1)</script>">Test</a>');
        $this->assertStringNotContainsString('data:', $result);

        $result = HtmlBuilder::clean('<img src="data:image/svg+xml,<svg onload=alert(1)>">');
        $this->assertStringNotContainsString('data:', $result);
    }

    public function testCleanRemovesEventHandlers()
    {
        $result = HtmlBuilder::clean('<div onload="alert(1)">content</div>');
        $this->assertStringNotContainsString('onload', $result);

        $result = HtmlBuilder::clean('<img src="x" onerror="alert(1)">');
        $this->assertStringNotContainsString('onerror', $result);

        $result = HtmlBuilder::clean('<body onmouseover="alert(1)">');
        $this->assertStringNotContainsString('onmouseover', $result);

        $result = HtmlBuilder::clean('<div onclick="alert(1)">click me</div>');
        $this->assertStringNotContainsString('onclick', $result);

        $result = HtmlBuilder::clean('<input onfocus="alert(1)">');
        $this->assertStringNotContainsString('onfocus', $result);
    }

    public function testCleanRemovesDangerousTags()
    {
        $result = HtmlBuilder::clean('<iframe src="evil.html"></iframe>');
        $this->assertStringNotContainsString('<iframe', $result);

        $result = HtmlBuilder::clean('<object data="malicious.swf"></object>');
        $this->assertStringNotContainsString('<object', $result);

        $result = HtmlBuilder::clean('<embed src="evil.swf">');
        $this->assertStringNotContainsString('<embed', $result);

        $result = HtmlBuilder::clean('<applet code="malicious.class"></applet>');
        $this->assertStringNotContainsString('<applet', $result);

        $result = HtmlBuilder::clean('<meta http-equiv="refresh" content="0;url=evil.html">');
        $this->assertStringNotContainsString('<meta', $result);

        $result = HtmlBuilder::clean('<link rel="stylesheet" href="evil.css">');
        $this->assertStringNotContainsString('<link', $result);

        $result = HtmlBuilder::clean('<base href="http://evil.com/">');
        $this->assertStringNotContainsString('<base', $result);

        $result = HtmlBuilder::clean('<bgsound src="evil.mid">');
        $this->assertStringNotContainsString('<bgsound', $result);

        $result = HtmlBuilder::clean('<frame src="evil.html">');
        $this->assertStringNotContainsString('<frame', $result);

        $result = HtmlBuilder::clean('<frameset><frame src="evil.html"></frameset>');
        $this->assertStringNotContainsString('<frameset', $result);
    }

    public function testCleanRemovesStyleTags()
    {
        $result = HtmlBuilder::clean('<style>body { background: url("javascript:alert(1)"); }</style>');
        $this->assertStringNotContainsString('<style', $result);

        $result = HtmlBuilder::clean('<style>@import "evil.css";</style>');
        $this->assertStringNotContainsString('<style', $result);
    }

    public function testCleanRemovesXmlNamespacedTags()
    {
        $result = HtmlBuilder::clean('<xml:namespace prefix="o" ns="urn:schemas-microsoft-com:office:office">');
        $this->assertStringNotContainsString('xml:', $result);

        $result = HtmlBuilder::clean('<o:p>Office paragraph</o:p>');
        $this->assertStringNotContainsString('<o:p', $result);
    }

    public function testCleanRemovesMozBinding()
    {
        $result = HtmlBuilder::clean('<div style="-moz-binding:url(\'http://evil.com/xss.xml#xss\')">content</div>');
        $this->assertStringNotContainsString('-moz-binding', $result);
    }

    public function testCleanPreservesValidHtml()
    {
        $result = HtmlBuilder::clean('<p>Hello <strong>world</strong></p>');
        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('<strong>', $result);

        $result = HtmlBuilder::clean('<a href="https://example.com">Link</a>');
        $this->assertStringContainsString('href="https://example.com"', $result);

        $result = HtmlBuilder::clean('<ul><li>Item 1</li><li>Item 2</li></ul>');
        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<li>', $result);

        $result = HtmlBuilder::clean('<blockquote>A quote</blockquote>');
        $this->assertStringContainsString('<blockquote>', $result);

        $result = HtmlBuilder::clean('<table><tr><td>Cell</td></tr></table>');
        $this->assertStringContainsString('<table>', $result);
        $this->assertStringContainsString('<td>', $result);
    }

    public function testCleanHandlesEntityEncodedAttacks()
    {
        // Hex encoded javascript
        $result = HtmlBuilder::clean('<a href="&#x6A;&#x61;&#x76;&#x61;&#x73;&#x63;&#x72;&#x69;&#x70;&#x74;:alert(1)">Test</a>');
        $this->assertStringNotContainsString('javascript:', $result);

        // Decimal encoded
        $result = HtmlBuilder::clean('<a href="&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;:alert(1)">Test</a>');
        $this->assertStringNotContainsString('javascript:', $result);
    }

    public function testCleanCaseInsensitive()
    {
        $result = HtmlBuilder::clean('<SCRIPT>alert(1)</SCRIPT>');
        $this->assertStringNotContainsString('<SCRIPT', $result);
        $this->assertStringNotContainsString('<script', strtolower($result));

        $result = HtmlBuilder::clean('<ScRiPt>alert(1)</ScRiPt>');
        $this->assertStringNotContainsString('<ScRiPt', $result);

        $result = HtmlBuilder::clean('<div ONLOAD="alert(1)">test</div>');
        $this->assertStringNotContainsString('ONLOAD', $result);
        $this->assertStringNotContainsString('onload', strtolower($result));
    }

    public function testCleanHandlesNestedAttacks()
    {
        // Nested script tags
        $result = HtmlBuilder::clean('<scr<script>ipt>alert(1)</scr</script>ipt>');
        $this->assertStringNotContainsString('<script', strtolower($result));

        // Double encoding
        $result = HtmlBuilder::clean('<a href="java&amp;#115;cript:alert(1)">Test</a>');
        $this->assertStringNotContainsString('javascript:', $result);
    }

    //
    // cleanVector() tests - SVG sanitization
    //

    public function testCleanVectorRemovesOnEventHandlers()
    {
        $result = HtmlBuilder::cleanVector('<svg onload="alert(1)"></svg>');
        $this->assertStringNotContainsString('onload', $result);

        $result = HtmlBuilder::cleanVector('<svg onclick="alert(1)"></svg>');
        $this->assertStringNotContainsString('onclick', $result);

        $result = HtmlBuilder::cleanVector('<svg onmouseover="alert(1)"></svg>');
        $this->assertStringNotContainsString('onmouseover', $result);

        $result = HtmlBuilder::cleanVector('<svg onerror="alert(1)"></svg>');
        $this->assertStringNotContainsString('onerror', $result);
    }

    public function testCleanVectorBypassAttemptWithEmbeddedQuote()
    {
        // This bypass attempt uses a=">" to trick simple regex parsers
        $result = HtmlBuilder::cleanVector('<svg xmlns="http://www.w3.org/2000/svg" a=">" onload="alert(1)"></svg>');
        $this->assertStringNotContainsString('onload', $result);

        $result = HtmlBuilder::cleanVector("<svg xmlns='http://www.w3.org/2000/svg' a='>' onload='alert(1)'></svg>");
        $this->assertStringNotContainsString('onload', $result);
    }

    public function testCleanVectorRemovesJavaScriptProtocol()
    {
        $result = HtmlBuilder::cleanVector('<svg><a href="javascript:alert(1)">click</a></svg>');
        $this->assertStringNotContainsString('javascript:', $result);

        // With entity encoding
        $result = HtmlBuilder::cleanVector('<svg><a href="&#106;avascript:alert(1)">click</a></svg>');
        $this->assertStringNotContainsString('javascript:', $result);
    }

    public function testCleanVectorRemovesDangerousTags()
    {
        $result = HtmlBuilder::cleanVector('<svg><script>alert(1)</script></svg>');
        $this->assertStringNotContainsString('<script', $result);

        $result = HtmlBuilder::cleanVector('<svg><object data="malicious.swf"></object></svg>');
        $this->assertStringNotContainsString('<object', $result);

        $result = HtmlBuilder::cleanVector('<svg><iframe src="evil.html"></iframe></svg>');
        $this->assertStringNotContainsString('<iframe', $result);

        $result = HtmlBuilder::cleanVector('<svg><embed src="evil.swf"></embed></svg>');
        $this->assertStringNotContainsString('<embed', $result);

        $result = HtmlBuilder::cleanVector('<svg><foreignObject><body onload="alert(1)"/></foreignObject></svg>');
        $this->assertStringNotContainsString('<foreignObject', $result);
    }

    public function testCleanVectorPreservesValidSvgContent()
    {
        // Basic SVG structure
        $result = HtmlBuilder::cleanVector('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="80" height="80" fill="red"/></svg>');
        $this->assertStringContainsString('<svg', $result);
        $this->assertStringContainsString('<rect', $result);

        // Path element
        $result = HtmlBuilder::cleanVector('<svg><path d="M10 10 L90 90" stroke="black"/></svg>');
        $this->assertStringContainsString('<path', $result);
        $this->assertStringContainsString('d="M10 10 L90 90"', $result);

        // Circle element
        $result = HtmlBuilder::cleanVector('<svg><circle cx="50" cy="50" r="40" fill="blue"/></svg>');
        $this->assertStringContainsString('<circle', $result);

        // Text element
        $result = HtmlBuilder::cleanVector('<svg><text x="10" y="20">Hello SVG</text></svg>');
        $this->assertStringContainsString('<text', $result);

        // Title element
        $result = HtmlBuilder::cleanVector('<svg><title>My SVG</title></svg>');
        $this->assertStringContainsString('<title>My SVG</title>', $result);

        // Style element (internal styles)
        $result = HtmlBuilder::cleanVector('<svg><style>.cls{fill:red}</style><rect class="cls"/></svg>');
        $this->assertStringContainsString('<style>', $result);
    }

    public function testCleanVectorPreservesGradients()
    {
        $svg = '<svg><defs><linearGradient id="grad1"><stop offset="0%" stop-color="red"/><stop offset="100%" stop-color="blue"/></linearGradient></defs><rect fill="url(#grad1)"/></svg>';
        $result = HtmlBuilder::cleanVector($svg);
        $this->assertStringContainsString('<linearGradient', $result);
        $this->assertStringContainsString('<stop', $result);
    }

    public function testCleanVectorPreservesFilters()
    {
        $svg = '<svg><defs><filter id="blur"><feGaussianBlur stdDeviation="5"/></filter></defs><rect filter="url(#blur)"/></svg>';
        $result = HtmlBuilder::cleanVector($svg);
        $this->assertStringContainsString('<filter', $result);
        $this->assertStringContainsString('<feGaussianBlur', $result);
    }

    public function testCleanVectorCaseInsensitive()
    {
        $result = HtmlBuilder::cleanVector('<svg ONLOAD="alert(1)"></svg>');
        $this->assertStringNotContainsString('ONLOAD', $result);
        $this->assertStringNotContainsString('onload', strtolower($result));

        $result = HtmlBuilder::cleanVector('<svg OnLoAd="alert(1)"></svg>');
        $this->assertStringNotContainsString('OnLoAd', $result);

        $result = HtmlBuilder::cleanVector('<svg><SCRIPT>alert(1)</SCRIPT></svg>');
        $this->assertStringNotContainsString('<SCRIPT', $result);
        $this->assertStringNotContainsString('<script', strtolower($result));
    }

    public function testCleanVectorRemovesDataProtocol()
    {
        $result = HtmlBuilder::cleanVector('<svg><a href="data:text/html,<script>alert(1)</script>">click</a></svg>');
        $this->assertStringNotContainsString('data:', $result);
    }

    public function testCleanVectorRemovesOnEventHandlers()
    {
        // Basic onload attribute - double quotes
        $result = HtmlBuilder::cleanVector('<svg onload="alert(1)"></svg>');
        $this->assertEquals('<svg></svg>', $result);

        // Basic onclick attribute - single quotes
        $result = HtmlBuilder::cleanVector('<svg onclick=\'alert(1)\'></svg>');
        $this->assertEquals('<svg></svg>', $result);

        // Unquoted event handler
        $result = HtmlBuilder::cleanVector('<svg onload=alert(1)></svg>');
        $this->assertEquals('<svg></svg>', $result);

        // Multiple event handlers
        $result = HtmlBuilder::cleanVector('<svg onload="alert(1)" onclick="alert(2)"></svg>');
        $this->assertEquals('<svg></svg>', $result);

        // Event handler with other attributes
        $result = HtmlBuilder::cleanVector('<svg width="100" onload="alert(1)" height="100"></svg>');
        $this->assertEquals('<svg width="100" height="100"></svg>', $result);

        // Various event types
        $result = HtmlBuilder::cleanVector('<svg onmouseover="alert(1)"></svg>');
        $this->assertEquals('<svg></svg>', $result);

        $result = HtmlBuilder::cleanVector('<svg onerror="alert(1)"></svg>');
        $this->assertEquals('<svg></svg>', $result);

        $result = HtmlBuilder::cleanVector('<svg onfocus="alert(1)"></svg>');
        $this->assertEquals('<svg></svg>', $result);
    }

    public function testCleanVectorBypassAttemptWithEmbeddedQuote()
    {
        // This is the specific bypass: a=">" tricks simple regex into thinking tag ends early
        $result = HtmlBuilder::cleanVector('<svg xmlns="http://www.w3.org/2000/svg" a=">" onload="alert(1)"></svg>');
        $this->assertStringNotContainsString('onload', $result);

        // Variation with single quotes
        $result = HtmlBuilder::cleanVector("<svg xmlns='http://www.w3.org/2000/svg' a='>' onload='alert(1)'></svg>");
        $this->assertStringNotContainsString('onload', $result);

        // Multiple fake closures
        $result = HtmlBuilder::cleanVector('<svg a=">" b=">" onload="alert(1)"></svg>');
        $this->assertStringNotContainsString('onload', $result);
    }

    public function testCleanVectorRemovesJavaScriptProtocol()
    {
        $result = HtmlBuilder::cleanVector('<svg><a href="javascript:alert(1)">click</a></svg>');
        $this->assertStringContainsString('nojavascript', $result);
        $this->assertStringNotContainsString('javascript:', $result);

        // With whitespace obfuscation
        $result = HtmlBuilder::cleanVector('<svg><a href="java script:alert(1)">click</a></svg>');
        $this->assertStringNotContainsString('javascript:', $result);

        // With entity encoding
        $result = HtmlBuilder::cleanVector('<svg><a href="&#106;avascript:alert(1)">click</a></svg>');
        $this->assertStringContainsString('nojavascript', $result);
    }

    public function testCleanVectorRemovesVbScriptProtocol()
    {
        $result = HtmlBuilder::cleanVector('<svg><a href="vbscript:alert(1)">click</a></svg>');
        $this->assertStringContainsString('novbscript', $result);
        $this->assertStringNotContainsString('vbscript:', $result);
    }

    public function testCleanVectorRemovesDangerousTags()
    {
        // Script tag
        $result = HtmlBuilder::cleanVector('<svg><script>alert(1)</script></svg>');
        $this->assertStringNotContainsString('<script', $result);
        $this->assertStringNotContainsString('</script', $result);

        // Object tag
        $result = HtmlBuilder::cleanVector('<svg><object data="malicious.swf"></object></svg>');
        $this->assertStringNotContainsString('<object', $result);

        // Iframe tag
        $result = HtmlBuilder::cleanVector('<svg><iframe src="evil.html"></iframe></svg>');
        $this->assertStringNotContainsString('<iframe', $result);

        // Embed tag
        $result = HtmlBuilder::cleanVector('<svg><embed src="evil.swf"></embed></svg>');
        $this->assertStringNotContainsString('<embed', $result);
    }

    public function testCleanVectorRemovesNamespacedElements()
    {
        $result = HtmlBuilder::cleanVector('<svg><foo:bar onload="alert(1)">test</foo:bar></svg>');
        $this->assertStringNotContainsString('foo:bar', $result);

        $result = HtmlBuilder::cleanVector('<svg><xlink:href="javascript:alert(1)"/></svg>');
        $this->assertStringNotContainsString('xlink:', $result);
    }

    public function testCleanVectorPreservesValidSvgContent()
    {
        // Basic SVG structure should remain intact
        $result = HtmlBuilder::cleanVector('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect x="10" y="10" width="80" height="80" fill="red"/></svg>');
        $this->assertStringContainsString('<svg', $result);
        $this->assertStringContainsString('xmlns=', $result);
        $this->assertStringContainsString('<rect', $result);
        $this->assertStringContainsString('fill="red"', $result);

        // Style attribute should be preserved
        $result = HtmlBuilder::cleanVector('<svg><rect style="fill:blue;"/></svg>');
        $this->assertStringContainsString('style="fill:blue;"', $result);

        // Title element should be preserved (allowed in cleanVector)
        $result = HtmlBuilder::cleanVector('<svg><title>My SVG</title></svg>');
        $this->assertStringContainsString('<title>My SVG</title>', $result);
    }

    public function testCleanVectorCaseInsensitive()
    {
        // Uppercase event handlers
        $result = HtmlBuilder::cleanVector('<svg ONLOAD="alert(1)"></svg>');
        $this->assertStringNotContainsString('ONLOAD', $result);
        $this->assertStringNotContainsString('onload', strtolower($result));

        // Mixed case
        $result = HtmlBuilder::cleanVector('<svg OnLoAd="alert(1)"></svg>');
        $this->assertStringNotContainsString('OnLoAd', $result);

        // Uppercase tags
        $result = HtmlBuilder::cleanVector('<svg><SCRIPT>alert(1)</SCRIPT></svg>');
        $this->assertStringNotContainsString('<SCRIPT', $result);
    }

    public function testCleanVectorDataProtocol()
    {
        $result = HtmlBuilder::cleanVector('<svg><a href="data:text/html,<script>alert(1)</script>">click</a></svg>');
        $this->assertStringContainsString('nodata', $result);
        $this->assertStringNotContainsString('data:', $result);
    }
}
