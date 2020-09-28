<?php

use October\Rain\Foundation\Application;

class ApplicationTest extends TestCase
{
    protected function setUp(): void
    {
        $this->basePath = 'custom-path';
        $this->app = new Application($this->basePath);
    }

    public function testPathMethods()
    {
        $this->assertEquals($this->app->pluginsPath(), $this->app->buildPath('/plugins'));
        $this->assertEquals($this->app->themesPath(), $this->app->buildPath('/themes'));
        $this->assertEquals($this->app->tempPath(), $this->app->buildPath('/storage/temp'));
        $this->assertEquals($this->app->uploadsPath(), $this->app->buildPath('/storage/app/uploads'));
        $this->assertEquals($this->app->mediaPath(), $this->app->buildPath('/storage/app/media'));
    }

    public function testSetPathMethods()
    {
        foreach (['plugins', 'themes', 'temp', 'uploads', 'media'] as $type) {
            $getter = $type . 'Path';
            $setter = 'set' . ucfirst($type) . 'Path';
            $path = $this->app->buildPath('/my' . ucfirst($type) . '/custom/path');
            $this->app->{$setter}($path);
            $this->assertEquals($this->app->{$getter}(), $path);
        }
    }
}
