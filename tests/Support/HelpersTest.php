<?php

class HelpersTest extends TestCase
{
    public function testConfigPath()
    {
        $this->assertEquals(app('path.config'), config_path());
    }

    public function testPluginsPath()
    {
        $this->assertEquals(app('path.plugins'), plugins_path());
    }

    public function testThemesPath()
    {
        $this->assertEquals(app('path.themes'), themes_path());
    }

    public function testTempPath()
    {
        $this->assertEquals(app('path.temp'), temp_path());
    }

    public function testUploadsPath()
    {
        $this->assertEquals(Config::get('cms.storage.uploads.path', app('path.uploads')), uploads_path());
    }

    public function testMediaPath()
    {
        $this->assertEquals(Config::get('cms.storage.media.path', app('path.media')), media_path());
    }

    public function testPathSuffix()
    {
        $path = '/extra-path';
        $types = ['temp', 'plugins', 'themes'];
        foreach ($types as $type) {
            $method = $type . '_path';
            $this->assertEquals(app('path.' . $type) . $path, $method($path));
            $this->assertNotEquals(app('path.' . $type) . DIRECTORY_SEPARATOR . $path, $method($path));
        }
    }

    public function testPathSuffixWithConfig()
    {
        $path = 'extra-path';
        $types = ['uploads', 'media'];
        foreach ($types as $type) {
            $method = $type . '_path';
            $config = 'cms.storage.' . $type . '.path';
            $this->assertEquals(Config::get($config, app('path.' . $type)) . DIRECTORY_SEPARATOR . $path, $method($path));
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
