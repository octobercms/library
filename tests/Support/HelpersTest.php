<?php

class HelpersTest extends TestCase
{
    public function testPluginsPath()
    {
        $this->assertEquals(plugins_path(), $this->app->pluginsPath());
    }

    public function testThemesPath()
    {
        $this->assertEquals(themes_path(), $this->app->themesPath());
    }

    public function testTempPath()
    {
        $this->assertEquals(temp_path(), $this->app->tempPath());
    }

    public function testUploadsPath()
    {
        $this->assertEquals(uploads_path(), Config::get('cms.storage.uploads.path', $this->app->uploadsPath()));
    }

    public function testMediaPath()
    {
        $this->assertEquals(media_path(), Config::get('cms.storage.media.path', $this->app->mediaPath()));
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
