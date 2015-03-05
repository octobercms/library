<?php

use October\Rain\Syntax\Parser;

class ParserTest extends TestCase
{

    public function testParseToTwig()
    {
        $content = '<h1>{text name="websiteName" label="Website Name"}Our wonderful website{/text}</h1>';
        $syntax = Parser::parse($content);
        $result = $syntax->toTwig();
        $this->assertEquals('<h1>{{ websiteName }}</h1>', $result);
    }

    public function testParseRepeaterToTwig()
    {
        $content = '';
        $content .= '{repeater name="websiteRepeater" label="Website Repeater"}'.PHP_EOL;
            $content .= '<h1>{text name="websiteName" label="Website Name"}Our wonderful website{/text}</h1>'.PHP_EOL;
            $content .= '{textarea name="websiteContent" label="Website Content"}Here are all the reasons we like our website{/textarea}'.PHP_EOL;
        $content .= '{/repeater}'.PHP_EOL;
        $syntax = Parser::parse($content);
        $result = $syntax->toTwig();

        $expected = '';
        $expected .= '{% for fields in websiteRepeater %}'.PHP_EOL;
        $expected .= '<h1>{{ fields.websiteName }}</h1>'.PHP_EOL;
        $expected .= '{{ fields.websiteContent }}'.PHP_EOL;
        $expected .= '{% endfor %}'.PHP_EOL;

        $this->assertEquals($expected, $result);
    }

    public function testParseToView()
    {
        $content = '<h1>{text name="websiteName" label="Website Name"}Our wonderful website{/text}</h1>';
        $syntax = Parser::parse($content);
        $result = $syntax->toView();
        $this->assertEquals('<h1>{websiteName}</h1>', $result);
    }

    public function testParseRepeaterToView()
    {
        $content = '';
        $content .= '{repeater name="websiteRepeater" label="Website Repeater"}'.PHP_EOL;
            $content .= '<h1>{text name="websiteName" label="Website Name"}Our wonderful website{/text}</h1>'.PHP_EOL;
            $content .= '{textarea name="websiteContent" label="Website Content"}Here are all the reasons we like our website{/textarea}'.PHP_EOL;
        $content .= '{/repeater}'.PHP_EOL;
        $syntax = Parser::parse($content);
        $result = $syntax->toView();

        $expected = '';
        $expected .= '{websiteRepeater}'.PHP_EOL;
        $expected .= '<h1>{websiteName}</h1>'.PHP_EOL;
        $expected .= '{websiteContent}'.PHP_EOL;
        $expected .= '{/websiteRepeater}'.PHP_EOL;

        $this->assertEquals($expected, $result);
    }

    public function testParseToEdit()
    {
        $content = '<h1>{text name="websiteName" label="Website Name"}Our wonderful website{/text}</h1>';
        $syntax = Parser::parse($content);
        $result = $syntax->toEditor();

        $this->assertArrayHasKey('websiteName', $result);
        $this->assertArrayHasKey('type', $result['websiteName']);
        $this->assertArrayHasKey('default', $result['websiteName']);
        $this->assertArrayHasKey('label', $result['websiteName']);
        $this->assertEquals('text', $result['websiteName']['type']);
        $this->assertEquals('Our wonderful website', $result['websiteName']['default']);
        $this->assertEquals('Website Name', $result['websiteName']['label']);
    }

    public function testParseToRender()
    {
        $content = '<h1>{text name="websiteName" label="Website Name"}Our wonderful website{/text}</h1>';
        $syntax = Parser::parse($content);

        $result = $syntax->render();
        $this->assertEquals('<h1>Our wonderful website</h1>', $result);

        $result = $syntax->render(['websiteName' => 'Your awesome web page']);
        $this->assertEquals('<h1>Your awesome web page</h1>', $result);
    }

    public function testParseRepeaterToRender()
    {
        $content = '';
        $content .= '{repeater name="websiteRepeater" label="Website Repeater"}'.PHP_EOL;
            $content .= '<h1>{text name="websiteName" label="Website Name"}Our wonderful website{/text}</h1>'.PHP_EOL;
            $content .= '{textarea name="websiteContent" label="Website Content"}Here are all the reasons we like our website{/textarea}'.PHP_EOL;
        $content .= '{/repeater}'.PHP_EOL;
        $syntax = Parser::parse($content);

        /*
         * Default content
         */
        $result = $syntax->render();
        $expected = '';
        $expected .= PHP_EOL; // Repeater open
        $expected .= '<h1>Our wonderful website</h1>'.PHP_EOL;
        $expected .= 'Here are all the reasons we like our website'.PHP_EOL;
        $expected .= PHP_EOL; // Repeater close
        $this->assertEquals($expected, $result);

        /*
         * Multiple repeats
         */
        $sampleData = ['websiteRepeater' => [
            [
                'websiteName' => 'Moo',
                'websiteContent' => 'Cow',
            ],
            [
                'websiteName' => 'Foo',
                'websiteContent' => 'Bar',
            ]
        ]];

        $result = $syntax->render($sampleData);
        $expected = '';
        $expected .= PHP_EOL; // Repeater open
        $expected .= '<h1>Moo</h1>'.PHP_EOL;
        $expected .= 'Cow'.PHP_EOL;
        $expected .= PHP_EOL; // Repeater divide
        $expected .= '<h1>Foo</h1>'.PHP_EOL;
        $expected .= 'Bar'.PHP_EOL;
        $expected .= PHP_EOL; // Repeater close
        $this->assertEquals($expected, $result);
    }

}