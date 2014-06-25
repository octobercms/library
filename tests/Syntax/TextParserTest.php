<?php

use October\Rain\Syntax\TextParser;

class TextParserTest extends TestCase
{

    public function testParseCombination()
    {
        $content = '{welcome}';
        $content .= '{posts}{title}{/posts}';
        $vars = [
            'welcome' => 'Hello!',
            'posts' => [
                ['title' => 'Foo'],
                ['title' => 'Bar'],
            ]
        ];
        $result = TextParser::parse($content, $vars);
        $this->assertEquals('Hello!FooBar', $result);
    }

    public function testParseSingleKey()
    {
        $content = '{foo} {foo} {foo}';
        $vars = ['foo' => 'bar'];
        $result = TextParser::parse($content, $vars);
        $this->assertEquals('bar bar bar', $result);
    }

    public function testParseLoopingKey()
    {
        $content = '';
        $content .= '{posts}{title}{/posts}';
        $content .= '{posts}{title}{/posts}';
        $vars = ['posts' => [
            ['title' => 'Dog'],
            ['title' => 'Cat'],
        ]];
        $result = TextParser::parse($content, $vars);
        $this->assertEquals('DogCatDogCat', $result);
    }

}