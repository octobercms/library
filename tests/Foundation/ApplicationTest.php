<?php

use October\Rain\Foundation\Application

class ApplicationTest extends TestCase
{
    public function testPathMethods()
    {
        $app = new Application();

        $this->assertEquals($app->pluginsPath(), '/plugins');
        $this->assertEquals($app->themesPath(), '/themes');
        $this->assertEquals($app->tempPath(), '/storage/temp');
        $this->assertEquals($app->uploadsPath(), '/storage/app/uploads');
        $this->assertEquals($app->mediaPath(), '/storage/app/media');
    }

    public function testSetPathMethods()
    {
        $app = new Application();

        foreach (['plugins', 'themes', 'temp', 'uploads', 'media'] as $type) { 
            $getter = $type . 'Path';
            $setter = 'set' . ucfirst($type) . 'Path';
            $path = '/my' . ucfirst($type) . 'Path';
            $app->{$setter}($path);
            $this->assertEquals($app->{$getter}(), $path);
        }
    }
}
