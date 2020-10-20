<?php

use October\Rain\Foundation\Application;
use October\Rain\Filesystem\PathResolver;

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
        $expected = PathResolver::join($this->basePath, $path);

        $this->assertEquals($expected, $this->app->buildPath($path));
        $this->assertEquals($expected, $this->app->buildPath('/' . $path));
        $this->assertEquals($expected, $this->app->buildPath('//' . $path));

        $prefix = '/prefix_path';
        $expected = PathResolver::join($prefix, $path);

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
            $expected = PathResolver::standardize($path);
            $this->app->{$setter}($path);

            $this->assertEquals($expected, $this->app->{$getter}());
        }
    }
}
