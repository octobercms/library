<?php

use October\Rain\Foundation\Application;

class ApplicationTest extends TestCase
{
    public function setUp()
    {
        $this->app = new Application();
    }

    public function testPathMethods()
    {
        $this->assertEquals($this->app->pluginsPath(), '/plugins');
        $this->assertEquals($this->app->themesPath(), '/themes');
        $this->assertEquals($this->app->tempPath(), '/storage/temp');
        $this->assertEquals($this->app->uploadsPath(), '/storage/app/uploads');
        $this->assertEquals($this->app->mediaPath(), '/storage/app/media');
    }

    public function testSetPathMethods()
    {
        foreach (['plugins', 'themes', 'temp', 'uploads', 'media'] as $type) {
            $getter = $type . 'Path';
            $setter = 'set' . ucfirst($type) . 'Path';
            $path = '/my' . ucfirst($type) . 'Path';
            $app->{$setter}($path);
            $this->assertEquals($this->app->{$getter}(), $path);
        }
    }
}
