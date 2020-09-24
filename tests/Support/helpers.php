<?php

use Config;
use October\Rain\Support\helpers;
use October\Rain\Foundation\Application;

class HelpersTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $basePath = __DIR__ . '/../fixtures/';
        $this->app = new Application($basePath);
    }

    public function testUploadsPath()
    {
        $this->assertEquals(uploads_path(), $app->uploadsPath());
    }

    public function testMediaPath()
    {
        $this->assertEquals(media_path(), $app->pluginsPath());
    }
}
