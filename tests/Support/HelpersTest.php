<?php

use October\Rain\Filesystem\PathResolver;

class HelpersTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Config::set('cms.storage.media.path', '/storage/app/custom-media-path');
        Config::set('cms.storage.uploads.path', '/storage/app/custom-uploads-path');
    }

    public function testConfigPath()
    {
        $this->assertEquals(app('path.config'), config_path());
    }

    public function testPluginsPath()
    {
        $expected = app('path.plugins');

        $this->assertEquals($expected, plugins_path());
        $this->assertEquals(PathResolver::join($expected, '/extra'), plugins_path('/extra'));
    }

    public function testThemesPath()
    {
        $expected = app('path.themes');

        $this->assertEquals($expected, themes_path());
        $this->assertEquals(PathResolver::join($expected, '/extra'), themes_path('/extra'));
    }

    public function testTempPath()
    {
        $expected = app('path.temp');
        $this->assertEquals($expected, temp_path());
        $this->assertEquals(PathResolver::join($expected, '/extra'), temp_path('/extra'));
    }

    public function testUploadsPath()
    {
        $expected = Config::get('cms.storage.uploads.path', app('path.uploads'));
        $expected = PathResolver::standardize($expected);

        $this->assertEquals($expected, uploads_path());
        $this->assertEquals(PathResolver::join($expected, '/extra'), uploads_path('/extra'));
    }

    public function testMediaPath()
    {
        $expected = Config::get('cms.storage.media.path', app('path.media'));
        $expected = PathResolver::standardize($expected);

        $this->assertEquals($expected, media_path());
        $this->assertEquals(PathResolver::join($expected, '/extra'), media_path('/extra'));
    }
}
