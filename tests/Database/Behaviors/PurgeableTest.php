<?php

use October\Rain\Database\Model;

class PurgeableTest extends TestCase
{
    public function testDirectImplementation()
    {
        $model = new TestModelDirect();
        
        $this->assertEquals(['October.Rain.Database.Behaviors.Purgeable'], $model->implement);
        $this->assertEquals(['purgeable'], $model->purgeable);
    }
    
    public function testDirectImplementationWithoutProperty()
    {
        $model = new TestModelWithoutProperty();
        
        $this->assertEquals(['October.Rain.Database.Behaviors.Purgeable'], $model->implement);
        $this->assertEquals(['purgeable'], $model->purgeable);
    }

    public function testDynamicImplementation()
    {
        $model = new TestModelDynamic();
        
        TestModelDynamic::extend(function($model) {
            $model->implement[] = 'October.Rain.Database.Behaviors.Purgeable';
            $model->addDynamicProperty('purgeable', []);
        });
        
        $this->assertEquals(['October.Rain.Database.Behaviors.Purgeable'], $model->implement);
        $this->assertEquals(['purgeable'], $model->purgeable);
    }
    
    public function testDynamicImplementationWithoutProperty()
    {
        $model = new TestModelDynamic();
        
        TestModelDynamic::extend(function($model) {
            $model->implement[] = 'October.Rain.Database.Behaviors.Purgeable';
        });
        
        $this->assertEquals(['October.Rain.Database.Behaviors.Purgeable'], $model->implement);
        $this->assertEquals(['purgeable'], $model->purgeable);
    }
}

/*
 * Class with implementation in the class itself
 */
class TestModelDirect extends Model
{
    public $implement = [
        'October.Rain.Database.Behaviors.Purgeable'
    ];
    
    public $purgeable = [];
}

/*
 * Class with implementation in the class itself but without property
 */
class TestModelDirectWithoutProperty extends Model
{
    public $implement = [
        'October.Rain.Database.Behaviors.Purgeable'
    ];
}


/*
 * Class with no implementation that can be extended
 */
class TestModelDynamic extends Model
{

}
