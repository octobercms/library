<?php

use October\Rain\Parse\Template as TextParser;

class TemplateTest extends TestCase
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
        // $content .= '{posts}{sound}{/posts}';
        $vars = ['posts' => [
            ['title' => 'Dog', 'sound' => 'Woof!'],
            ['title' => 'Cat', 'sound' => 'Meow!'],
        ]];
        $result = TextParser::parse($content, $vars);
        $this->assertEquals('DogCat', $result);
    }

    public function testParseWithFilters()
    {
        $filters = [];
        $filters['upper'] = function($value) { return strtoupper($value); };
        $filters['lower'] = function($value) { return strtolower($value); };

        $content = '';
        $content .= '{foo} {foo|upper} {foo|lower} ';
        $content .= '{posts}{title}{title|upper}{title|lower}{/posts}';
        $vars = [
            'foo' => 'Bar',
            'posts' => [
                ['title' => 'Dog'],
                ['title' => 'Cat'],
            ]
        ];
        $result = TextParser::parse($content, $vars, ['filters' => $filters]);
        $this->assertEquals('Bar BAR bar DogDOGdogCatCATcat', $result);
    }

}