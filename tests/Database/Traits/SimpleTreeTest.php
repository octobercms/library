<?php

use Illuminate\Database\Capsule\Manager;
use October\Rain\Database\Model;

/**
 * SimpleTreeTest
 */
class SimpleTreeTest extends TestCase
{
    /**
     * setUp test
     */
    public function setUp(): void
    {
        $capsule = new Manager;
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $capsule->schema()->create('test_simple_tree', function ($table) {
            $table->increments('id');
            $table->integer('parent_id')->nullable();
            $table->string('name')->nullable();
        });
    }

    /**
     * testGetAllChildrenKeepsDepthFirstOrder confirms nested children follow their parent
     */
    public function testGetAllChildrenKeepsDepthFirstOrder()
    {
        $root = TestSimpleTreeNode::create(['name' => 'Root']);
        $first = TestSimpleTreeNode::create(['name' => 'First', 'parent_id' => $root->id]);
        TestSimpleTreeNode::create(['name' => 'Second', 'parent_id' => $root->id]);
        TestSimpleTreeNode::create(['name' => 'First Child', 'parent_id' => $first->id]);

        $names = TestSimpleTreeNode::find($root->id)->getAllChildren()->pluck('name')->all();

        $this->assertSame(['First', 'First Child', 'Second'], $names);
        $this->assertSame(3, TestSimpleTreeNode::find($root->id)->getChildCount());
    }

    /**
     * testParentLoopDoesNotRecurseForever confirms records that point at each other are listed once
     */
    public function testParentLoopDoesNotRecurseForever()
    {
        $first = TestSimpleTreeNode::create(['name' => 'First']);
        $second = TestSimpleTreeNode::create(['name' => 'Second', 'parent_id' => $first->id]);
        $first->parent_id = $second->id;
        $first->save();

        $own = TestSimpleTreeNode::create(['name' => 'Own']);
        $own->parent_id = $own->id;
        $own->save();

        $this->assertSame(['Second'], TestSimpleTreeNode::find($first->id)->getAllChildren()->pluck('name')->all());
        $this->assertCount(2, TestSimpleTreeNode::find($first->id)->getParents());
        $this->assertCount(0, TestSimpleTreeNode::find($own->id)->getAllChildren());
        $this->assertCount(1, TestSimpleTreeNode::find($own->id)->getParents());
    }
}

/**
 * TestSimpleTreeNode example class
 */
class TestSimpleTreeNode extends Model
{
    use \October\Rain\Database\Traits\SimpleTree;

    protected $table = 'test_simple_tree';

    protected $fillable = ['name', 'parent_id'];

    public $timestamps = false;
}
