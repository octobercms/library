<?php

use October\Rain\Database\Attach\File as AttachFile;

class FileTest extends LaravelTestCase
{
    public function testFromFile()
    {
        $original = Config::set('filesystems.default');

        $localPath = base_path() . '/../fixtures/database/attach/hello.txt';

        // case: local
        Config::set('filesystems.default', 'local');
        $file = new AttachFile();
        $file->fromFile($localPath);
        $this->assertFileExists(Config::get('filesystems.disks.local.root') . '/' . $file->getDiskPath());

        // clean up crated file
        File::deleteDirectory(Config::get('filesystems.disks.local.root'));


        // case: public
        Config::set('filesystems.default', 'public');
        $file = new AttachFile();
        $file->fromFile($localPath);
        $this->assertFileExists(Config::get('filesystems.disks.public.root') . '/' . $file->getDiskPath());

        // clean up crated file
        File::deleteDirectory(Config::get('filesystems.disks.public.root'));


        Config::set('filesystems.default', $original);

    }
}
