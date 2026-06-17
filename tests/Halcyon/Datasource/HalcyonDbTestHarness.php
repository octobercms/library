<?php

use October\Rain\Halcyon\Datasource\DbDatasource;

class HalcyonDbTestHarness
{
    protected static ?Illuminate\Database\Capsule\Manager $capsule = null;

    protected static bool $facadeAppSaved = false;

    protected static $savedFacadeApp;

    public static function boot(string $table): DbDatasource
    {
        if (!self::$capsule) {
            self::$capsule = new Illuminate\Database\Capsule\Manager;
            self::$capsule->addConnection([
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
            self::$capsule->setAsGlobal();
            self::$capsule->bootEloquent();
        }

        if (!self::$facadeAppSaved) {
            self::$savedFacadeApp = Illuminate\Support\Facades\Facade::getFacadeApplication();
            self::$facadeAppSaved = true;
        }

        $app = new Illuminate\Container\Container;
        $app->singleton('db', fn () => self::$capsule->getDatabaseManager());
        Illuminate\Support\Facades\Facade::setFacadeApplication($app);

        $schema = self::$capsule->schema();
        $schema->dropIfExists($table);
        $schema->create($table, function ($table) {
            $table->string('source');
            $table->string('path');
            $table->text('content')->nullable();
            $table->integer('file_size')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        self::clearDbDatasourceCache();

        return new DbDatasource('test-theme', $table);
    }

    public static function teardown(): void
    {
        if (self::$facadeAppSaved) {
            Illuminate\Support\Facades\Facade::setFacadeApplication(self::$savedFacadeApp);
        }
    }

    public static function clearDbDatasourceCache(): void
    {
        $reflection = new ReflectionClass(DbDatasource::class);

        foreach (['pathCache', 'mtimeCache', 'trashedPathCache'] as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $property->setValue(null, []);
        }
    }
}
