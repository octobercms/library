<?php

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use October\Rain\Database\DatabaseServiceProvider;
use October\Rain\Database\Schema\Blueprint;

/**
 * BlueprintBindingTest
 */
class BlueprintBindingTest extends TestCase
{
    /**
     * testEveryConnectionUsesTheRainBlueprint confirms schema builders outside the db.schema binding get the Rain blueprint
     */
    public function testEveryConnectionUsesTheRainBlueprint()
    {
        $previous = Container::getInstance();

        try {
            $app = new Container;
            Container::setInstance($app);
            self::callProtectedMethod(new DatabaseServiceProvider($app), 'registerConnectionServices');

            $capsule = new Manager;
            $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], 'other');

            $blueprint = null;
            $capsule->getConnection('other')->getSchemaBuilder()->create('test_blueprint_binding', function ($table) use (&$blueprint) {
                $blueprint = $table;
                $table->increments('id');
            });

            $this->assertInstanceOf(Blueprint::class, $blueprint);
        }
        finally {
            Container::setInstance($previous);
        }
    }
}
