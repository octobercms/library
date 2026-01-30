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

        $result = with(new HtmlBuilder)->limit('<p>The quick brown fox’s jumped over the lazy dog</p>', 25, '!!!');
        $this->assertEquals('<p>The quick brown fox’s jum!!!</p>', $result);

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

    public function testClean()
    {
        $result = with(new HtmlBuilder)->clean('<script>window.location = "http://google.com"</script>');
        $this->assertEquals('window.location = "http://google.com"', $result);

        $result = with(new HtmlBuilder)->clean('<span style="width: expression(alert(\'Ping!\'));"></span>');
        $this->assertEquals('<span ></span>', $result);

        $result = with(new HtmlBuilder)->clean('<a href="javascript: alert(\'Ping!\');">Test</a>');
        $this->assertEquals('<a href="nojavascript... alert(\'Ping!\');">Test</a>', $result);

        $result = with(new HtmlBuilder)->clean('<a href=" &#14;  javascript: alert(\'Ping!\');">Test</a>');
        $this->assertEquals('<a href="nojavascript... alert(\'Ping!\');">Test</a>', $result);

        $result = with(new HtmlBuilder)->clean('<a href=" &#14  javascript: alert(\'Ping!\');">Test</a>');
        $this->assertEquals('<a href="nojavascript... alert(\'Ping!\');">Test</a>', $result);

        $result = with(new HtmlBuilder)->clean('<a href=" &#14;;  javascript: alert(\'Ping!\');">Test</a>');
        $this->assertEquals('<a href="nojavascript... alert(\'Ping!\');">Test</a>', $result);
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
