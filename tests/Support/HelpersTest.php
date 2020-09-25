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
        $this->assertEquals(uploads_path(), $this->app['config']->get('cms.storage.uploads.path', $this->app->uploadsPath()));
    }

    public function testMediaPath()
    {
        $this->assertEquals(media_path(), $this->app['config']->get('cms.storage.media.path', $this->app->mediaPath()));
    }
}
