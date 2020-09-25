<?php

use October\Rain\Foundation\Application;

class ApplicationTest extends TestCase
{
    protected function setUp():void
    {
        $this->basePath = '/custom-path';
        $this->app = new Application($this->basePath);
    }

    public function testPathMethods()
    {
        $this->assertEquals($this->app->pluginsPath(), $this->basePath . '/plugins');
        $this->assertEquals($this->app->themesPath(), $this->basePath . '/themes');
        $this->assertEquals($this->app->tempPath(), $this->basePath . '/storage/temp');
        $this->assertEquals($this->app->uploadsPath(), $this->basePath . '/storage/app/uploads');
        $this->assertEquals($this->app->mediaPath(), $this->basePath . '/storage/app/media');
    }

    public function testSetPathMethods()
    {
        foreach (['plugins', 'themes', 'temp', 'uploads', 'media'] as $type) {
            $getter = $type . 'Path';
            $setter = 'set' . ucfirst($type) . 'Path';
            $path = $this->basePath . '/my' . ucfirst($type) . 'Path';
            $this->app->{$setter}($path);
            $this->assertEquals($this->app->{$getter}(), $path);
        }
    }
}
