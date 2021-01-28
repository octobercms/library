<?php

use October\Rain\Foundation\Application;
use October\Rain\Filesystem\PathResolver;

class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        // Mock application
        $this->app = new Application('/tmp/custom-path');

        // Mock Config facade
        if (!class_exists('Config')) {
            class_alias('October\Rain\Support\Facades\Config', 'Config');
        }

        Config::shouldReceive('get')->andreturnUsing(function ($key) {
            switch ($key) {
                case 'cms.storage.uploads.path':
                    return '/storage/app/custom-uploads-path';
                case 'cms.storage.media.path':
                    return '/storage/app/custom-media-path';
            }
        });
    }
    
    public function testConfigPath()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestIncomplete('Need to fix Windows testing here');
        }

        $this->assertEquals($this->app['path.config'], config_path());
    }

    public function testPluginsPath()
    {
        $expected = $this->app['path.plugins'];

        $this->assertEquals($expected, plugins_path());
        $this->assertEquals(PathResolver::join($expected, '/extra'), plugins_path('/extra'));
    }

    public function testThemesPath()
    {
        $expected = $this->app['path.themes'];

        $this->assertEquals($expected, themes_path());
        $this->assertEquals(PathResolver::join($expected, '/extra'), themes_path('/extra'));
    }

    public function testTempPath()
    {
        $expected = $this->app['path.temp'];

        $this->assertEquals($expected, temp_path());
        $this->assertEquals(PathResolver::join($expected, '/extra'), temp_path('/extra'));
    }

    public function testUploadsPath()
    {
        $expected = PathResolver::standardize(Config::get('cms.storage.uploads.path'));

        $this->assertEquals($expected, uploads_path());
        $this->assertEquals(PathResolver::join($expected, '/extra'), uploads_path('/extra'));
    }

    public function testMediaPath()
    {
        $expected = PathResolver::standardize(Config::get('cms.storage.media.path'));

        $this->assertEquals($expected, media_path());
        $this->assertEquals(PathResolver::join($expected, '/extra'), media_path('/extra'));
    }
}
