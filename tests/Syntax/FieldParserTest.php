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

}