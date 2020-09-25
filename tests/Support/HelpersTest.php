<?php

use October\Rain\Foundation\Application;

class HelpersTest extends TestCase
{
    public function createApplication()
    {
        $this->basePath = realpath(__DIR__.'/../fixtures');
        $app = new Application($this->basePath);

        $app->singleton(
            Illuminate\Contracts\Console\Kernel::class,
            October\Rain\Foundation\Console\Kernel::class
        );

        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        return $app;
    }

    public function testPluginsPath()
    {
        $this->assertEquals(plugins_path(), $this->basePath . '/plugins');
    }

    public function testThemesPath()
    {
        $this->assertEquals(themes_path(), $this->basePath . '/themes');
    }

    public function testTempPath()
    {
        $this->assertEquals(temp_path(), $this->basePath . '/storage/temp');
    }

    public function testUploadsPath()
    {
        $this->assertEquals(uploads_path(), $this->app['config']->get('cms.storage.uploads.path'));
    }

    public function testMediaPath()
    {
        $this->assertEquals(media_path(), $this->app['config']->get('cms.storage.media.path'));
    }

}
