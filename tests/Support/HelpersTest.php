<?php

class HelpersTest extends TestCase
{
    public function testConfigPath()
    {
        $this->assertEquals(config_path(), app('path.config'));
    }

    public function testPluginsPath()
    {
        $this->assertEquals(plugins_path(), app('path.plugins'));
    }

    public function testThemesPath()
    {
        $this->assertEquals(themes_path(), app('path.themes'));
    }

    public function testTempPath()
    {
        $this->assertEquals(temp_path(), app('path.temp'));
    }

    public function testUploadsPath()
    {
        $this->assertEquals(uploads_path(), Config::get('cms.storage.uploads.path', app('path.uploads')));
    }

    public function testMediaPath()
    {
        $this->assertEquals(media_path(), Config::get('cms.storage.media.path', app('path.media')));
    }

    public function testPathSuffix()
    {
        $path = 'extra-path';
        $types = ['config', 'temp', 'plugins', 'themes'];
        foreach ($types as $type) {
            $method = $type.'_path';
            $this->assertEquals($method($path), app('path.'.$type).DIRECTORY_SEPARATOR.$path);
        }
    }
    public function testPathSuffixWithConfig()
    {
        $path = 'extra-path';
        $types = ['uploads', 'media'];
        foreach ($types as $type) {
            $method = $type.'_path';
            $config = 'cms.storage.'.$type.'.path';
            $this->assertEquals($method($path), Config::get($config, app('path.'.$type)).DIRECTORY_SEPARATOR.$path);
        }
    }
}

// stub class for config facade
class Config
{
    public static function get($key, $default = null)
    {
        switch ($key) {
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

    public static function package($namespace, $hint)
    {
    }
}
