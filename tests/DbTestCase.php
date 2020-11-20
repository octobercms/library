<?php

use October\Rain\Database\Model;
use October\Rain\Database\Pivot;
use Illuminate\Database\Capsule\Manager as CapsuleManager;
use October\Rain\Events\Dispatcher;

class DbTestCase extends TestCase
{
    public function setUp()
    {
        $this->db = new CapsuleManager;
        $this->db->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => ''
        ]);

        $this->db->setAsGlobal();
        $this->db->bootEloquent();

        Model::setEventDispatcher(new Dispatcher());
    }

    public function tearDown()
    {
        $this->flushModelEventListeners();
        parent::tearDown();
        unset($this->db);
    }

    /**
     * The models in October use a static property to store their events, these
     * will need to be targeted and reset ready for a new test cycle.
     * Pivot models are an exception since they are internally managed.
     * @return void
     */
    protected function flushModelEventListeners()
    {
        foreach (get_declared_classes() as $class) {
            if ($class === Pivot::class) {
                continue;
            }

            $reflectClass = new ReflectionClass($class);
            if (
                !$reflectClass->isInstantiable() ||
                !$reflectClass->isSubclassOf(Model::class) ||
                $reflectClass->isSubclassOf(Pivot::class)
            ) {
                continue;
            }

            $class::flushEventListeners();
        }

        Model::flushEventListeners();
    }
}
