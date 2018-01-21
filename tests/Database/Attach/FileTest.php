<?php

use File as FileHelper;
use October\Rain\Database\Attach\File;
use Orchestra\Testbench\TestCase;

class FileTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app->setBasePath(__DIR__);
        $app['config']->set('filesystems', [
            'default' => 'local',
            'cloud' => 's3',
            'disks' => [

                'local' => [
                    'driver' => 'local',
                    'root' => __DIR__ . '/app',
                ],

                'public' => [
                    'driver' => 'local',
                    'root' => __DIR__ . '/app/public',
                    'url' => env('APP_URL').'/storage',
                    'visibility' => 'public',
                ],
            ],
        ]);
        $app['config']->set('app.debug', 'true');
    }

    public function testFromFile()
    {
        // Create test file
        $localPath = __DIR__ . '/hello.txt';
        FileHelper::put($localPath, 'Hello World!');

        // case: local
        Config::set('filesystems.default', 'local');
        $file = new File();
        $file->fromFile($localPath);
        Storage::disk('local')->assertExists($file->getDiskPath());

        // clean up crated file
        FileHelper::deleteDirectory(Config::get('filesystems.disks.local.root'));


        // case: public
        Config::set('filesystems.default', 'public');
        $file = new File();
        $file->fromFile($localPath);
        Storage::disk('public')->assertExists($file->getDiskPath());

        // clean up crated file
        FileHelper::deleteDirectory(Config::get('filesystems.disks.public.root'));

        FileHelper::delete($localPath);
    }
}
