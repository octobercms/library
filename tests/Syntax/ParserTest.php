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

}