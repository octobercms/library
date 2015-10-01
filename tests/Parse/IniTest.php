<?php

use October\Rain\Parse\Ini as IniParser;

class IniTest extends TestCase
{

    public function testBasic()
    {
        $content = <<<ESC
title = "Plugin components"
url = "/demo/plugins"
layout = "default"

[demoTodo]
min = 1.2
max = 3
ESC;
        $content = preg_replace('~\R~u', PHP_EOL, $content); // Normalize EOL

        $vars = [
            'title' => 'Plugin components',
            'url' => '/demo/plugins',
            'layout' => 'default',
            'demoTodo' => [
                'min' => 1.2,
                'max' => 3
            ]
        ];

        $parser = new IniParser;
        $result = $parser->parse($content);
        $this->assertCount(4, $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('layout', $result);
        $this->assertEquals('Plugin components', $result['title']);
        $this->assertEquals('/demo/plugins', $result['url']);
        $this->assertEquals('default', $result['layout']);
        $this->assertArrayHasKey('demoTodo', $result);
        $this->assertArrayHasKey('max', $result['demoTodo']);
        $this->assertArrayHasKey('min', $result['demoTodo']);
        $this->assertEquals(1.2, $result['demoTodo']['min']);
        $this->assertEquals(3, $result['demoTodo']['max']);
        $this->assertEquals($vars, $result);

        $result = $parser->render($vars);
        $this->assertEquals($content, $result);
    }

    public function testArray()
    {
        $content = <<<ESC
[products]
excludeStatuses[] = 1
excludeStatuses[] = 42
excludeStatuses[] = 69
ESC;
        $content = preg_replace('~\R~u', PHP_EOL, $content); // Normalize EOL

        $vars = [
            'products' => [
                'excludeStatuses' => [1, 42, 69]
            ]
        ];

        $parser = new IniParser;
        $result = $parser->parse($content);
        $this->assertArrayHasKey('products', $result);
        $this->assertArrayHasKey('excludeStatuses', $result['products']);
        $this->assertCount(3, $result['products']['excludeStatuses']);
        $this->assertEquals($vars, $result);

        $result = $parser->render($vars);
        $this->assertEquals($content, $result);
    }

    public function testObject()
    {
        $content = <<<ESC
[viewBag]
code = "signin-snippet"
name = "Sign in snippet"
properties[type] = "string"
properties[title] = "Redirection page"
properties[default] = "/clients"
ESC;
        $content = preg_replace('~\R~u', PHP_EOL, $content); // Normalize EOL

        $vars = [
            'viewBag' => [
                'code' => 'signin-snippet',
                'name' => 'Sign in snippet',
                'properties' => [
                    'type' => 'string',
                    'title' => 'Redirection page',
                    'default' => '/clients'
                ]
            ]
        ];

        $parser = new IniParser;
        $result = $parser->parse($content);
        $this->assertArrayHasKey('viewBag', $result);
        $this->assertArrayHasKey('properties', $result['viewBag']);
        $this->assertArrayHasKey('type', $result['viewBag']['properties']);
        $this->assertArrayHasKey('title', $result['viewBag']['properties']);
        $this->assertArrayHasKey('default', $result['viewBag']['properties']);
        $this->assertEquals($vars, $result);

        $result = $parser->render($vars);
        $this->assertEquals($content, $result);
    }

    public function testComments()
    {
        $content = <<<ESC
; last modified 1 April 2001 by John Doe
[owner]
name=John Doe
; name=Adam Person
organization=Acme Widgets Inc.

[database]
; use IP address in case network name resolution is not working
server=192.0.2.62
; server=127.0.0.1
port=143
file="payroll.dat"
ESC;
        $content = preg_replace('~\R~u', PHP_EOL, $content); // Normalize EOL

        $vars = [
            'owner' => [
                'name' => 'John Doe',
                'organization' => 'Acme Widgets Inc.',
            ],
            'database' => [
                'server' => '192.0.2.62',
                'port' => '143',
                'file' => 'payroll.dat',
            ]
        ];

        $parser = new IniParser;
        $result = $parser->parse($content);
        $this->assertArrayHasKey('owner', $result);
        $this->assertArrayHasKey('name', $result['owner']);
        $this->assertArrayHasKey('organization', $result['owner']);
        $this->assertArrayHasKey('database', $result);
        $this->assertArrayHasKey('server', $result['database']);
        $this->assertArrayHasKey('port', $result['database']);
        $this->assertArrayHasKey('file', $result['database']);
        $this->assertEquals($vars, $result);

        $content = <<<ESC
[owner]
name = "John Doe"
organization = "Acme Widgets Inc."

[database]
server = "192.0.2.62"
port = 143
file = "payroll.dat"
ESC;
        $content = preg_replace('~\R~u', PHP_EOL, $content); // Normalize EOL

        $result = $parser->render($vars);
        $this->assertEquals($content, $result);
    }

    public function testComplex()
    {
        $content = <<<ESC
firstLevelValue = "relax"
firstLevelArray[] = "foo"
firstLevelArray[] = "bar"

[someComponent]
secondLevelArray[] = "hello"
secondLevelArray[] = "world"
name[title] = "column_name_name"
name[validation][required][message] = "column_name_required"
name[validation][regex][pattern] = "^[0-9_a-z]+$"
name[validation][regex][message] = "column_validation_title"
type[title] = "column_name_type"
type[type] = "dropdown"
type[options][integer] = "Integer"
type[options][smallInteger] = "Small Integer"
type[options][bigInteger] = "Big Integer"
type[options][date] = "Date"
type[options][time] = "Time"
type[options][dateTime] = "Date and Time"
type[options][timestamp] = "Timestamp"
type[options][string] = "String"
type[options][text] = "Text"
type[options][binary] = "Binary"
type[options][boolean] = "Boolean"
type[options][decimal] = "Decimal"
type[options][double] = "Double"
type[validation][required][message] = "column_type_required"
modes[title] = "column_name_type"
modes[type] = "checkboxlist"
modes[options][] = 12
modes[options][] = 34
modes[options][] = 56
modes[options][] = 78
modes[options][] = 99
security[title] = "column_name_security"
security[type] = "radio"
security[options][all][] = "All"
security[options][all][] = "Everyone"
security[options][users][] = "Users"
security[options][users][] = "Users only"
security[options][guests][] = "Guests"
security[options][guests][] = "Guests only"
length[title] = "column_name_length"
length[validation][regex][pattern] = "(^[0-9]+$)|(^[0-9]+,[0-9]+$)"
length[validation][regex][message] = "column_validation_length"
unsigned[title] = "column_name_unsigned"
unsigned[type] = "checkbox"
allow_null[title] = "column_name_nullable"
allow_null[type] = "checkbox"
auto_increment[title] = "column_auto_increment"
auto_increment[type] = "checkbox"
primary_key[title] = "column_auto_primary_key"
primary_key[type] = "checkbox"
primary_key[width] = "50px"
default[title] = "column_default"
ESC;
        $content = preg_replace('~\R~u', PHP_EOL, $content); // Normalize EOL

        $vars = [
            'firstLevelValue' => 'relax',
            'firstLevelArray' => ['foo', 'bar'],
            'someComponent' => [
                'secondLevelArray' => ['hello', 'world'],
                'name' => [
                    'title' => 'column_name_name',
                    'validation' => [
                        'required' => [
                            'message' => 'column_name_required'
                        ],
                        'regex' => [
                            'pattern' => '^[0-9_a-z]+$',
                            'message' => 'column_validation_title'
                        ]
                    ]
                ],
                'type' => [
                    'title' => 'column_name_type',
                    'type' => 'dropdown',
                    'options' => [
                        'integer' => 'Integer',
                        'smallInteger' => 'Small Integer',
                        'bigInteger' => 'Big Integer',
                        'date' => 'Date',
                        'time' => 'Time',
                        'dateTime' => 'Date and Time',
                        'timestamp' => 'Timestamp',
                        'string' => 'String',
                        'text' => 'Text',
                        'binary' => 'Binary',
                        'boolean' => 'Boolean',
                        'decimal' => 'Decimal',
                        'double' => 'Double'
                    ],
                    'validation' => [
                        'required' => [
                            'message' => 'column_type_required'
                        ]
                    ]
                ],
                'modes' => [
                    'title' => 'column_name_type',
                    'type' => 'checkboxlist',
                    'options' => [12, 34, 56, 78, 99]
                ],
                'security' => [
                    'title' => 'column_name_security',
                    'type' => 'radio',
                    'options' => [
                        'all' => ['All', 'Everyone'],
                        'users' => ['Users', 'Users only'],
                        'guests' => ['Guests', 'Guests only']
                    ]
                ],
                'length' => [
                    'title' => 'column_name_length',
                    'validation' => [
                        'regex' => [
                            'pattern' => '(^[0-9]+$)|(^[0-9]+,[0-9]+$)',
                            'message' => 'column_validation_length'
                        ]
                    ]
                ],
                'unsigned' => [
                    'title' => 'column_name_unsigned',
                    'type' => 'checkbox'
                ],
                'allow_null' => [
                    'title' => 'column_name_nullable',
                    'type' => 'checkbox'
                ],
                'auto_increment' => [
                    'title' => 'column_auto_increment',
                    'type' => 'checkbox'
                ],
                'primary_key' => [
                    'title' => 'column_auto_primary_key',
                    'type' => 'checkbox',
                    'width' => '50px'
                ],
                'default' => [
                    'title' => 'column_default'
                ]
            ]
        ];

        $parser = new IniParser;
        $result = $parser->parse($content);
        $this->assertEquals($vars, $result);

        $result = $parser->render($vars);
        $this->assertEquals($content, $result);
    }

}