<?php

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Facade;
use October\Rain\Database\Model;
use October\Rain\Extension\ExtensionBase;

/**
 * @BeforeMethods({"init"})
 * @Revs(1000)
 * @Iterations(5)
 */
class ModelBench
{
    /**
     * @var array rows for hydration
     */
    protected $rows = [];

    /**
     * @var ModelBenchPlainModel model
     */
    protected $model;

    /**
     * @var ModelBenchBehaviorModel behaviorModel
     */
    protected $behaviorModel;

    /**
     * init
     */
    public function init()
    {
        $app = new Container;
        $app->instance('app', $app);
        $capsule = new Manager($app);
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->setEventDispatcher(new Dispatcher($app));
        $capsule->bootEloquent();
        $app->instance('db', $capsule->getDatabaseManager());
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);

        for ($i = 1; $i <= 100; $i++) {
            $this->rows[] = [
                'id' => $i,
                'title' => 'Title '.$i,
                'slug' => 'title-'.$i,
                'excerpt' => 'Excerpt '.$i,
                'content' => str_repeat('Lorem ipsum ', 20),
                'author_id' => $i % 10,
                'category_id' => $i % 5,
                'is_published' => 1,
                'published_at' => '2026-01-01 00:00:00',
                'sort_order' => $i,
                'options' => '{"color":"red","size":10}',
            ];
        }

        $this->model = ModelBenchPlainModel::hydrate([$this->rows[0]])->first();
        $this->behaviorModel = ModelBenchBehaviorModel::hydrate([$this->rows[0]])->first();

        if (
            $this->model->options['color'] !== 'red' ||
            $this->behaviorModel->getBenchValue() !== 'value' ||
            ModelBenchBehaviorModel::hydrate($this->rows)->count() !== 100
        ) {
            throw new RuntimeException('Model fixture mismatch');
        }
    }

    /**
     * @Subject
     */
    public function benchAttributeRead()
    {
        $this->readAttributes($this->model);
    }

    /**
     * @Subject
     */
    public function benchAttributeReadWithBehaviors()
    {
        $this->readAttributes($this->behaviorModel);
    }

    /**
     * @Subject
     */
    public function benchJsonableRead()
    {
        $this->model->options;
    }

    /**
     * @Subject
     * @Revs(100)
     */
    public function benchHydrate()
    {
        ModelBenchPlainModel::hydrate($this->rows);
    }

    /**
     * @Subject
     * @Revs(100)
     */
    public function benchHydrateWithBehaviors()
    {
        ModelBenchBehaviorModel::hydrate($this->rows);
    }

    /**
     * @Subject
     */
    public function benchCallBehaviorMethod()
    {
        $this->behaviorModel->getBenchValue();
    }

    /**
     * @Subject
     */
    public function benchCallForwardedQuery()
    {
        $this->model->where('id', 1);
    }

    /**
     * readAttributes reads ten plain attributes
     */
    protected function readAttributes($model)
    {
        $model->id;
        $model->title;
        $model->slug;
        $model->excerpt;
        $model->content;
        $model->author_id;
        $model->category_id;
        $model->is_published;
        $model->published_at;
        $model->sort_order;
    }
}

class ModelBenchPlainModel extends Model
{
    protected $table = 'bench_posts';

    protected $jsonable = ['options'];
}

class ModelBenchBehaviorModel extends Model
{
    public $implement = [
        ModelBenchBehaviorA::class,
        ModelBenchBehaviorB::class,
        ModelBenchBehaviorC::class,
    ];

    protected $table = 'bench_posts';

    protected $jsonable = ['options'];
}

class ModelBenchBehaviorA extends ExtensionBase
{
    public $behaviorA;
}

class ModelBenchBehaviorB extends ExtensionBase
{
    public $behaviorB;
}

class ModelBenchBehaviorC extends ExtensionBase
{
    public $behaviorC;

    public function getBenchValue()
    {
        return 'value';
    }
}
