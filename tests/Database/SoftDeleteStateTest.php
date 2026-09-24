<?php

use Illuminate\Database\Capsule\Manager;
use October\Rain\Database\Model;

class SoftDeleteStateTest extends TestCase
{
    protected $resolver;
    protected $dispatcher;
    protected $savedStatics = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->resolver = Model::getConnectionResolver();
        $this->dispatcher = Model::getEventDispatcher();
        foreach (['booted', 'eventsBooted'] as $name) {
            $property = new ReflectionProperty(Model::class, $name);
            $this->savedStatics[] = [$property, $property->getValue()];
            $property->setValue(null, []);
        }
        $capsule = new Manager;
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->setEventDispatcher(new \Illuminate\Events\Dispatcher(new \Illuminate\Container\Container));
        $capsule->bootEloquent();
        $capsule->getConnection()->getSchemaBuilder()->create('soft_delete_state_entries', function ($table) {
            $table->increments('id');
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function tearDown(): void
    {
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

    public function testNestedFailedForceDeleteRestoresTheOuterForceDeleteState()
    {
        $model = new SoftDeleteStateEntry;
        $model->save();
        $failure = new RuntimeException('Nested delete failed');
        $nested = false;
        $model->bindEvent('model.beforeDelete', function () use ($model, $failure, &$nested) {
            if ($nested) {
                throw $failure;
            }
            $nested = true;
            try {
                $model->forceDelete();
                $this->fail('The nested callback failure must propagate.');
            }
            catch (RuntimeException $exception) {
                $this->assertSame($failure, $exception);
            }
            $this->assertFalse($model->isSoftDelete());
            throw $failure;
        });

        try {
            $model->forceDelete();
            $this->fail('The outer callback failure must propagate.');
        }
        catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception);
        }
        $this->assertTrue($model->isSoftDelete());
        $this->assertNotNull(SoftDeleteStateEntry::find($model->id));
    }

    public function testFailedForceDeleteDoesNotTurnLaterDeleteIntoHardDelete()
    {
        $model = new SoftDeleteStateEntry;
        $model->save();
        $failure = new RuntimeException('Delete callback failed');
        $model->bindEvent('model.beforeDelete', function () use ($failure) {
            throw $failure;
        });

        try {
            $model->forceDelete();
            $this->fail('The callback failure must propagate.');
        }
        catch (RuntimeException $exception) {
            $this->assertSame($failure, $exception, $exception->getMessage());
        }

        $this->assertTrue($model->isSoftDelete());
        $model->unbindEvent('model.beforeDelete');
        $model->delete();
        $this->assertNull(SoftDeleteStateEntry::find($model->id));
        $this->assertNotNull(SoftDeleteStateEntry::withTrashed()->find($model->id));
    }
}

class SoftDeleteStateEntry extends Model
{
    use \October\Rain\Database\Traits\SoftDelete;

    protected $table = 'soft_delete_state_entries';
    public $timestamps = false;
}
