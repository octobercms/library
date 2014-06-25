<?php

use October\Rain\Syntax\FieldParser;

class FieldParserTest extends TestCase
{

// Array
// (
//     [0] => Array
//         (
//             [0] => {text name="websiteName" label="Website Name"}
//             [1] => {text name="blogName" label="Blog Name"}
//             [2] => {text name="storeName" label="Store Name"}
//         )

//     [1] => Array
//         (
//             [0] => name="websiteName" label="Website Name"
//             [1] => name="blogName" label="Blog Name"
//             [2] => name="storeName" label="Store Name"
//         )

// )

// Array
// (
//     [0] => Array
//         (
//             [0] => {text name="blogName" label="Blog Name"}OctoberCMS{/text}
//             [1] => {text name="storeName" label="Store Name"}{/text}
//         )

//     [1] => Array
//         (
//             [0] => name="blogName" label="Blog Name"
//             [1] => name="storeName" label="Store Name"
//         )

//     [2] => Array
//         (
//             [0] => OctoberCMS
//             [1] =>
//         )

// )

    public function testParse()
    {
        $content = '';
        $content .= '{text name="websiteName" label="Website Name"}'.PHP_EOL;
        $content .= '{text name="blogName" label="Blog Name"}OctoberCMS{/text}'.PHP_EOL;
        $content .= '{text name="storeName" label="Store Name"}{/text}';

        $result = FieldParser::parse($content);

    }

}