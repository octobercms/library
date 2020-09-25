<?php

use October\Rain\Foundation\Application;

class ApplicationTest extends TestCase
{
    protected function setUp():void
    {
        $this->basePath = 'custom-path';
        $this->app = new Application($this->basePath);
    }

    public function testPathMethods()
    {
        $this->assertEquals($this->app->pluginsPath(), $this->app->buildPath($this->basePath, '/plugins'));
        $this->assertEquals($this->app->themesPath(), $this->app->buildPath($this->basePath, '/themes'));
        $this->assertEquals($this->app->tempPath(), $this->app->buildPath($this->basePath, '/storage/temp'));
        $this->assertEquals($this->app->uploadsPath(), $this->app->buildPath($this->basePath, '/storage/app/uploads'));
        $this->assertEquals($this->app->mediaPath(), $this->app->buildPath($this->basePath, '/storage/app/media'));
    }

    public function testSetPathMethods()
    {
        foreach (['plugins', 'themes', 'temp', 'uploads', 'media'] as $type) {
            $getter = $type . 'Path';
            $setter = 'set' . ucfirst($type) . 'Path';
            $path = $this->app->buildPath($this->basePath, '/my' . ucfirst($type) . '/custom/path');
            $this->app->{$setter}($path);
            $this->assertEquals($this->app->{$getter}(), $path);
        }
    }
}
