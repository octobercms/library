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

    public function testPathMethods()
    {
        $this->assertEquals(PathResolver::join($this->basePath, '/plugins'), $this->app->pluginsPath());
        $this->assertEquals(PathResolver::join($this->basePath, '/themes'), $this->app->themesPath());
        $this->assertEquals(PathResolver::join($this->basePath, '/storage/temp'), $this->app->tempPath());
        $this->assertEquals(PathResolver::join($this->basePath, '/storage/app/uploads'), $this->app->uploadsPath());
        $this->assertEquals(PathResolver::join($this->basePath, '/storage/app/media'), $this->app->mediaPath());
    }

    public function testSetPathMethods()
    {
        foreach (['plugins', 'themes', 'temp', 'uploads', 'media'] as $type) {
            $getter = $type . 'Path';
            $setter = 'set' . ucfirst($type) . 'Path';

            $path = PathResolver::join('/my'.ucfirst($type), '/custom/path');
            $expected = PathResolver::standardize($path);
            $this->app->{$setter}($path);

            $this->assertEquals($expected, $this->app->{$getter}());
        }
    }
}
