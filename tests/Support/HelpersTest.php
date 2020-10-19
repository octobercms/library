<?php

use October\Rain\Filesystem\PathResolver;

class HelpersTest extends TestCase
{
    public function testConfigPath()
    {
        $this->assertEquals(app('path.config'), config_path());
    }

    public function testPluginsPath()
    {
        $path = Config::get('cms.pluginsPath');
        $expected = $path ? $this->app->buildPath($path) : app('path.plugins');

        $this->assertEquals($expected, plugins_path());
        $this->assertEquals(PathResolver::join($expected, '/extra'), plugins_path('/extra'));
    }

    public function testThemesPath()
    {
        $path = Config::get('cms.themesPath');
        $expected = $path ? $this->app->buildPath($path) : app('path.themes');

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
        $expected = str_replace('/', DIRECTORY_SEPARATOR, $expected);

        $this->assertEquals($expected, uploads_path());
        $this->assertEquals(PathResolver::join($expected, '/extra'), uploads_path('/extra'));
    }

    public function testMediaPath()
    {
        $expected = Config::get('cms.storage.media.path', app('path.media'));
        $expected = str_replace('/', DIRECTORY_SEPARATOR, $expected);

        $this->assertEquals($expected, media_path());
        $this->assertEquals(PathResolver::join($expected, '/extra'), media_path('/extra'));
    }
}

// stub class for config facade
class Config
{
    public static function get($key, $default = null)
    {
        switch ($key) {
            case 'cms.pluginsPath':
                $value = '/custom-plugins-path';
                break;
            case 'cms.themesPath':
                $value = '/custom-themes-path';
                break;
            case 'cms.storage.uploads.path':
                $value = '/storage/app/custom-uploads-path';
                break;
            case 'cms.storage.media.path':
                $value = '/storage/app/custom-media-path';
                break;
            case 'filesystems.disks':
                $value = [];
                break;
            default:
                $value = $default;
        }
        return $value;
    }

    public static function set($name, $value)
    {
    }

    public static function package($namespace, $hint)
    {
    }
}
