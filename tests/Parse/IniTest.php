<?php

use October\Rain\Parse\Ini as IniParser;

class IniTest extends TestCase
{

    public function testBasic()
    {
        $path = __DIR__.'/../fixtures/parse/basic.ini';
        $this->assertFileExists($path);
        $content = $this->getContents($path);

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
        $path = __DIR__.'/../fixtures/parse/array.ini';
        $this->assertFileExists($path);
        $content = $this->getContents($path);

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
        $path = __DIR__.'/../fixtures/parse/object.ini';
        $this->assertFileExists($path);
        $content = $this->getContents($path);

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
        $path = __DIR__.'/../fixtures/parse/comments.ini';
        $this->assertFileExists($path);
        $content = $this->getContents($path);

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

        $path = __DIR__.'/../fixtures/parse/comments-clean.ini';
        $this->assertFileExists($path);
        $content = $this->getContents($path);

        $content = preg_replace('~\R~u', PHP_EOL, $content); // Normalize EOL

        $result = $parser->render($vars);
        $this->assertEquals($content, $result);
    }

    public function testComplex()
    {
        $path = __DIR__.'/../fixtures/parse/complex.ini';
        $this->assertFileExists($path);
        $content = $this->getContents($path);

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

    public function testMultilinesValues()
    {
        $path = __DIR__.'/../fixtures/parse/multilines-value.ini';
        $this->assertFileExists($path);
        $content = $this->getContents($path);

        $vars = [
            'var' => "\\Test\\Path\\",
            'editorContent' =>
'<p>Some
    <br>"Multi-line"
    <br>text
</p>',
        ];

        $parser = new IniParser;
        $result = $parser->parse($content);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('var', $result);
        $this->assertArrayHasKey('editorContent', $result);

        // Ensures we do not care about EOL sequences
        $result['editorContent'] = str_replace("\r\n", "\n", $result['editorContent']);
        $vars['editorContent'] = str_replace("\r\n", "\n", $vars['editorContent']);

        $this->assertEquals($vars, $result);

        $result = $parser->render($vars);
        $content = str_replace("\r\n", "\n", $content);
        $result = str_replace("\r\n", "\n", $result);
        $this->assertEquals($content, $result);
    }

    public function testRender()
    {
        $parser = new IniParser;

        $data = [
            'var1'=>'value 1',
            'var2'=>'value 21'
        ];

        $path = __DIR__.'/../fixtures/parse/simple.ini';
        $this->assertFileExists($path);

        $str = $parser->render($data);

        $this->assertNotEmpty($str);
        $this->assertEquals($this->getContents($path), $str);

        $data = [
            'section' => [
                'sectionVar1' => 'section value 1',
                'sectionVar2' => 'section value 2'
            ],
            'section data' => [
                'sectionVar3' => 'section value 3',
                'sectionVar4' => 'section value 4'
            ],
            'emptysection' => [],
            'var1'=>'value 1',
            'var2'=>'value 21'
        ];

        $path = __DIR__.'/../fixtures/parse/sections.ini';
        $this->assertFileExists($path);

        $str = $parser->render($data);
        $this->assertEquals($this->getContents($path), $str);

        $data = [
            'section' => [
                'sectionVar1' => 'section value 1',
                'sectionVar2' => 'section value 2',
                'subsection' => [
                    'subsection value 1',
                    'subsection value 2'
                ],
                'sectionVar3' => 'section value 3'
            ],
            'section data' => [
                'sectionVar3' => 'section value 3',
                'sectionVar4' => 'section value 4',
                'subsection' => [
                    'subsection value 1',
                    'subsection value 2'
                ]
            ],
            'var1'=>'value 1',
            'var2'=>'value 21'
        ];

        $path = __DIR__.'/../fixtures/parse/subsections.ini';
        $this->assertFileExists($path);

        $str = $parser->render($data);
        $this->assertEquals($this->getContents($path), $str);
   }

   //
   // Helpers
   //

   protected function getContents($path)
   {
        $content = file_get_contents($path);
        $content = preg_replace('~\R~u', PHP_EOL, $content); // Normalize EOL
        return $content;
   }

}
