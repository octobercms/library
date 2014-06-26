<?php

use October\Rain\Syntax\FieldParser;

class FieldParserTest extends TestCase
{

    public function testParse()
    {
        $content = '';
        $content .= '{text name="websiteName" label="Website Name"}'.PHP_EOL;
        $content .= '{text name="blogName" label="Blog Name"}OctoberCMS{/text}'.PHP_EOL;
        $content .= '{text name="storeName" label="Store Name"}{/text}';

        $result = FieldParser::parse($content);

    }

    public function testProcessParamsRegex()
    {
        $parser = new FieldParser('');
        $content = 'name="test" comment="This is a test"';
        $result = $parser->processParamsRegex($content);
        print_r($result);
    }

}