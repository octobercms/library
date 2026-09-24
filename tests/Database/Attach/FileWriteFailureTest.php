<?php

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use October\Rain\Database\Attach\File;
use October\Rain\Database\Attach\FileException;
use October\Rain\Database\Model;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileWriteFailureTest extends TestCase
{
    protected $path;
    protected $files;
    protected $container;
    protected $facadeApplication;
    protected $dispatcher;
    protected $resolver;
    protected $savedStatics = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->container = Container::getInstance();
        $this->facadeApplication = Facade::getFacadeApplication();
        $this->dispatcher = Model::getEventDispatcher();
        $this->resolver = Model::getConnectionResolver();
        foreach ([[Facade::class, 'resolvedInstance'], [Model::class, 'booted'], [Model::class, 'eventsBooted']] as [$class, $name]) {
            $property = new ReflectionProperty($class, $name);
            $this->savedStatics[] = [$property, $property->getValue()];
            $property->setValue(null, []);
        }

        $this->path = sys_get_temp_dir().'/rain-file-write-'.uniqid();
        $this->files = new FileWriteFailureFilesystem;
        $this->files->makeDirectory($this->path.'/temp', 0755, true);
        $this->files->put($this->path.'/source.txt', 'Attachment content');
        $app = new Container;
        Container::setInstance($app);
        Facade::setFacadeApplication($app);
        $app->instance('files', $this->files);
        $app->instance('path.temp', $this->path.'/temp');
        $capsule = new Manager($app);
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->setEventDispatcher(new Dispatcher($app));
        $capsule->bootEloquent();
        $capsule->getConnection()->getSchemaBuilder()->create('file_write_entries', function ($table) {
            $table->increments('id');
            $table->string('file_name');
            $table->string('disk_name');
            $table->string('content_type');
            $table->integer('file_size');
            $table->integer('sort_order')->nullable();
        });
    }

    public function tearDown(): void
    {
        $this->files->deleteDirectory($this->path);
        Container::setInstance($this->container);
        Facade::setFacadeApplication($this->facadeApplication);
        if ($this->resolver) {
            Model::setConnectionResolver($this->resolver);
        }
        else {
            Model::unsetConnectionResolver();
        }
        if ($this->dispatcher) {
            Model::setEventDispatcher($this->dispatcher);
        }
        else {
            Model::unsetEventDispatcher();
        }
        foreach ($this->savedStatics as [$property, $value]) {
            $property->setValue(null, $value);
        }
        parent::tearDown();
    }

    public function testFromFileRejectsFailedLocalAndRemoteWrites()
    {
        foreach ([true, false] as $local) {
            $file = $this->makeFile($local);
            try {
                $file->fromFile($this->path.'/source.txt');
                $this->fail('A failed storage write must not report success.');
            }
            catch (FileException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }
            $this->assertSame(1, $local ? $this->files->copies : $file->disk->writes);
        }
    }

    public function testUploadedFileWriteFailureStopsMetadataSave()
    {
        foreach ([true, false] as $local) {
            $file = $this->makeFile($local);
            $file->data = new UploadedFile($this->path.'/source.txt', 'upload.txt', null, null, true);
            try {
                $file->save();
                $this->fail('A failed upload must abort the model save.');
            }
            catch (FileException $exception) {
                $this->assertFalse($file->exists);
            }
            $this->assertSame(0, FileWriteFailureEntry::count());
            $this->assertSame(1, $local ? $this->files->copies : $file->disk->writes);
        }
    }

    public function testFromDataCleansTemporaryFileWhenStorageWriteFails()
    {
        foreach ([true, false] as $local) {
            try {
                $this->makeFile($local)->fromData('Attachment content', 'data.txt');
                $this->fail('A failed data write must not report success.');
            }
            catch (FileException $exception) {
                $this->assertSame([], $this->files->files($this->path.'/temp'));
            }
        }
    }

    public function testFromDataRejectsFailedTemporaryWriteAndCleansPartialFile()
    {
        $this->files->succeed = true;
        $this->files->failTemporaryWrites = true;
        try {
            $this->makeFile(true)->fromData('Attachment content', 'data.txt');
            $this->fail('A failed temporary write must not produce a partial attachment.');
        }
        catch (FileException $exception) {
            $this->assertSame([], $this->files->files($this->path.'/temp'));
            $this->assertSame(0, $this->files->copies);
        }
    }

    public function testSuccessfulLocalAndRemoteWritesKeepReturningTheAttachment()
    {
        $this->files->succeed = true;
        foreach ([true, false] as $local) {
            foreach (['file', 'post', 'data'] as $source) {
                $file = $this->makeFile($local);
                $file->disk->succeed = true;
                if ($source === 'file') {
                    $result = $file->fromFile($this->path.'/source.txt');
                }
                elseif ($source === 'post') {
                    $result = $file->fromPost(new UploadedFile($this->path.'/source.txt', 'upload.txt', null, null, true));
                }
                else {
                    $result = $file->fromData('Attachment content', 'data.txt');
                }
                $this->assertSame($file, $result);
                $this->assertSame(18, $file->file_size);
                if ($local) {
                    $this->assertSame('Attachment content', $this->files->get($file->root.'/'.$file->getDiskPath()));
                }
            }
        }
        $this->assertSame([], $this->files->files($this->path.'/temp'));
    }

    protected function makeFile($local)
    {
        $file = new FileWriteFailureEntry;
        $file->local = $local;
        $file->root = $this->path.'/uploads';
        $file->disk = new FileWriteFailureDisk;
        return $file;
    }
}

class FileWriteFailureEntry extends File
{
    protected $table = 'file_write_entries';
    public $timestamps = false;
    public $local;
    public $root;
    public $disk;

    protected function isLocalStorage()
    {
        return $this->local;
    }

    protected function getLocalRootPath()
    {
        return $this->root;
    }

    public function getDisk()
    {
        return $this->disk;
    }
}

class FileWriteFailureFilesystem extends Filesystem
{
    public $copies = 0;
    public $succeed = false;
    public $failTemporaryWrites = false;

    public function put($path, $contents, $lock = false)
    {
        if ($this->failTemporaryWrites && str_ends_with($path, '.tmp')) {
            parent::put($path, 'partial', $lock);
            return false;
        }
        return parent::put($path, $contents, $lock);
    }

    public function copy($path, $target)
    {
        $this->copies++;
        return $this->succeed ? parent::copy($path, $target) : false;
    }
}

class FileWriteFailureDisk
{
    public $writes = 0;
    public $succeed = false;

    public function putFileAs($directory, $source, $name, $visibility)
    {
        $this->writes++;
        return $this->succeed ? $directory.'/'.$name : false;
    }
}
