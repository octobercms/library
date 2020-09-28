<?php

use October\Rain\Foundation\Application;

class ApplicationTest extends TestCase
{
    protected function setUp(): void
    {
        $this->basePath = 'custom-path';
        $this->app = new Application($this->basePath);
    }

    public function testBuildPath()
    {
        $path = 'dir/subdir';
        $expected = $this->basePath . '/' . $path;
        $expected = str_replace('/', DIRECTORY_SEPARATOR, $expected);

        $this->assertEquals($expected, $this->app->buildPath($path));
        $this->assertEquals($expected, $this->app->buildPath('/' . $path));
        $this->assertEquals($expected, $this->app->buildPath('//' . $path));

        $prefix = '/prefix_path';
        $expected = $prefix . '/' . $path;
        $expected = str_replace('/', DIRECTORY_SEPARATOR, $expected);

        $this->assertEquals($expected, $this->app->buildPath($path, $prefix));
        $this->assertEquals($expected, $this->app->buildPath('/' . $path, $prefix));
        $this->assertEquals($expected, $this->app->buildPath('//' . $path, $prefix));
    }

    public function testPathMethods()
    {
        $this->assertEquals($this->app->buildPath('/plugins'), $this->app->pluginsPath());
        $this->assertEquals($this->app->buildPath('/themes'), $this->app->themesPath());
        $this->assertEquals($this->app->buildPath('/storage/temp'), $this->app->tempPath());
        $this->assertEquals($this->app->buildPath('/storage/app/uploads'), $this->app->uploadsPath());
        $this->assertEquals($this->app->buildPath('/storage/app/media'), $this->app->mediaPath());
    }

    public function testSetPathMethods()
    {
        foreach (['plugins', 'themes', 'temp', 'uploads', 'media'] as $type) {
            $getter = $type . 'Path';
            $setter = 'set' . ucfirst($type) . 'Path';

            $path = '/my' . ucfirst($type) . '/custom/path';
            $expected = str_replace('/', DIRECTORY_SEPARATOR, $path);
            $this->app->{$setter}($path);

            $this->assertEquals($expected, $this->app->{$getter}());
        }
    }
}
