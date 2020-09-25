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
        $this->assertEquals($this->app->pluginsPath(), makePath($this->basePath, ['plugins']));
        $this->assertEquals($this->app->themesPath(), makePath($this->basePath, ['themes']));
        $this->assertEquals($this->app->tempPath(), makePath($this->basePath, ['storage', 'temp']));
        $this->assertEquals($this->app->uploadsPath(), makePath($this->basePath, ['storage','app','uploads']));
        $this->assertEquals($this->app->mediaPath(), makePath($this->basePath, ['storage','app','media']));
    }

    public function testSetPathMethods()
    {
        foreach (['plugins', 'themes', 'temp', 'uploads', 'media'] as $type) {
            $getter = $type . 'Path';
            $setter = 'set' . ucfirst($type) . 'Path';
            $path = $this->basePath . DIRECTORY_SEPARATOR . 'my' . ucfirst($type) . 'Path';
            $this->app->{$setter}($path);
            $this->assertEquals($this->app->{$getter}(), $path);
        }
    }
}

function makePath($base, $segments)
{
    return $base . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
}
