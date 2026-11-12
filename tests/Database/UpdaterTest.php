<?php

use October\Rain\Database\Updater;

class UpdaterTest extends TestCase
{
    protected $updater;

    public function setUp(): void
    {
        include_once __DIR__.'/../fixtures/database/SampleClass.php';

        $this->updater = new Updater();
    }

    public function testClassNameGetsParsedCorrectly()
    {
        $reflector = new ReflectionClass(TestPlugin\SampleClass::class);
        $filePath = $reflector->getFileName();

        $classFullName = $this->updater->getClassFromFile($filePath);

        $this->assertEquals(TestPlugin\SampleClass::class, $classFullName);
    }

    public function testMigrationRunsOnItsOwnConnection()
    {
        $capsule = new Illuminate\Database\Capsule\Manager;
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], 'other');
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $resolver = October\Rain\Database\Model::getConnectionResolver();

        $migration = new class extends October\Rain\Database\Updates\Migration {
            protected $connection = 'other';

            public $ranOn;

            public function up()
            {
                $this->ranOn = October\Rain\Database\Model::getConnectionResolver()->getDefaultConnection();
            }
        };

        self::callProtectedMethod($this->updater, 'runMethod', [$migration, 'up']);

        $this->assertSame('other', $migration->ranOn);
        $this->assertSame('default', $resolver->getDefaultConnection());
    }
}
