<?php

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use October\Rain\Database\Builder;
use October\Rain\Database\Collection;
use October\Rain\Database\Model;

/**
 * ModelAttributesTest
 */
class ModelAttributesTest extends TestCase
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
    }

    /**
     * testAttributesSelectCollectionAndBuilder confirms the Laravel class attributes apply to Rain models
     */
    public function testAttributesSelectCollectionAndBuilder()
    {
        $model = new TestAttributedModel;

        $this->assertInstanceOf(TestAttributedCollection::class, $model->newCollection());
        $this->assertInstanceOf(TestAttributedBuilder::class, $model->newQuery());
    }

    /**
     * testDefaultsStayTheRainClasses confirms models without attributes keep the Rain collection and builder
     */
    public function testDefaultsStayTheRainClasses()
    {
        $model = new TestPlainModel;

        $this->assertSame(Collection::class, get_class($model->newCollection()));
        $this->assertSame(Builder::class, get_class($model->newQuery()));
    }
}

class TestAttributedCollection extends Collection
{
}

class TestAttributedBuilder extends Builder
{
}

#[CollectedBy(TestAttributedCollection::class)]
#[UseEloquentBuilder(TestAttributedBuilder::class)]
class TestAttributedModel extends Model
{
    protected $table = 'test_attributed';
}

class TestPlainModel extends Model
{
    protected $table = 'test_plain';
}
