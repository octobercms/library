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
        $expected = Config::get('cms.storage.uploads.path', app('path.uploads'));
        $expected = str_replace('/', DIRECTORY_SEPARATOR, $expected);

        $this->assertEquals($expected, uploads_path());
    }

    public function testMediaPath()
    {
        $expected = Config::get('cms.storage.media.path', app('path.media'));
        $expected = str_replace('/', DIRECTORY_SEPARATOR, $expected);

        $this->assertEquals($expected, media_path());
    }

    public function testPathSuffix()
    {
        $path = '/extra-path';
        $types = ['temp', 'plugins', 'themes'];
        foreach ($types as $type) {
            $method = $type . '_path';
            $expected_path = str_replace('/', DIRECTORY_SEPARATOR, $path);
            $this->assertEquals(app('path.' . $type) . $expected_path, $method($path));
            $this->assertNotEquals(app('path.' . $type) . DIRECTORY_SEPARATOR . $expected_path, $method($path));
        }
    }

    public function testPathSuffixWithConfig()
    {
        $path = 'extra-path';
        $types = ['uploads', 'media'];
        foreach ($types as $type) {
            $method = $type . '_path';
            $config = 'cms.storage.' . $type . '.path';

            $expected = Config::get($config, app('path.' . $type)) . '/' . $path;
            $expected = str_replace('/', DIRECTORY_SEPARATOR, $expected);

            $this->assertEquals($expected, $method($path));
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
