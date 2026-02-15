<?php

use October\Rain\Parse\Ini;
use October\Rain\Parse\Bracket;
use October\Rain\Parse\Parsedown\ParsedownExtra;

/**
 * @BeforeMethods({"init"})
 * @Revs(1000)
 * @Iterations(5)
 */
class ParseBench
{
    /**
     * @var Ini
     */
    protected $ini;

    /**
     * @var Bracket
     */
    protected $bracket;

    /**
     * @var ParsedownExtra
     */
    protected $parsedown;

    /**
     * @var string
     */
    protected $iniContent;

    /**
     * @var array
     */
    protected $iniArray;

    /**
     * @var string
     */
    protected $bracketTemplate;

    /**
     * @var array
     */
    protected $bracketData;

    /**
     * @var string
     */
    protected $markdownSimple;

    /**
     * @var string
     */
    protected $markdownComplex;

    /**
     * init
     */
    public function init()
    {
        $this->ini = new Ini;
        $this->bracket = new Bracket;
        $this->parsedown = new ParsedownExtra;

        // INI content for parsing
        $this->iniContent = <<<INI
title = "My Application"
debug = true
version = 1.5

[database]
host = localhost
port = 3306
name = october_db

[cache]
driver = redis
prefix = app_
ttl = 3600
INI;

        // Array for INI rendering
        $this->iniArray = [
            'title' => 'My Application',
            'debug' => true,
            'version' => 1.5,
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
                'name' => 'october_db'
            ],
            'cache' => [
                'driver' => 'redis',
                'prefix' => 'app_',
                'ttl' => 3600
            ]
        ];

        // Bracket template
        $this->bracketTemplate = <<<TPL
Hello {name}, welcome to {site}!
Your account details:
- Email: {email}
- Role: {role}
{items}
  - {title}: {value}
{/items}
TPL;

        // Bracket data
        $this->bracketData = [
            'name' => 'John Doe',
            'site' => 'October CMS',
            'email' => 'john@example.com',
            'role' => 'Administrator',
            'items' => [
                ['title' => 'Posts', 'value' => '42'],
                ['title' => 'Comments', 'value' => '128'],
                ['title' => 'Likes', 'value' => '256']
            ]
        ];

        // Simple markdown
        $this->markdownSimple = '**Hello** _world_!';

        // Complex markdown
        $this->markdownComplex = <<<MD
# Welcome to October CMS

This is a **bold** statement with _italic_ text.

## Features

- Easy to use
- Powerful plugins
- Beautiful themes

### Code Example

```php
echo "Hello World";
```

> A blockquote for emphasis

[Visit our site](https://octobercms.com)
MD;
    }

    //
    // INI Benchmarks
    //

    /**
     * @Subject
     */
    public function benchIniParse()
    {
        $this->ini->parse($this->iniContent);
    }

    /**
     * @Subject
     */
    public function benchIniRender()
    {
        $this->ini->render($this->iniArray);
    }

    //
    // Bracket Benchmarks
    //

    /**
     * @Subject
     */
    public function benchBracketParseSimple()
    {
        $this->bracket->parseString('Hello {name}!', ['name' => 'World']);
    }

    /**
     * @Subject
     */
    public function benchBracketParseWithLoop()
    {
        $this->bracket->parseString($this->bracketTemplate, $this->bracketData);
    }

    //
    // Markdown Benchmarks (using ParsedownExtra directly to avoid facade dependency)
    //

    /**
     * @Subject
     */
    public function benchMarkdownParseSimple()
    {
        $this->parsedown->text($this->markdownSimple);
    }

    /**
     * @Subject
     */
    public function benchMarkdownParseComplex()
    {
        $this->parsedown->text($this->markdownComplex);
    }

    /**
     * @Subject
     */
    public function benchMarkdownParseLine()
    {
        $this->parsedown->line($this->markdownSimple);
    }
}
