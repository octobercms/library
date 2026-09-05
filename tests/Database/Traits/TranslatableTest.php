<?php

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Facade;
use October\Rain\Database\Model;

class TranslatableTest extends TestCase
{
    protected $capsule;
    protected $savedContainer;
    protected $savedFacadeApplication;
    protected $savedDispatcher;
    protected $savedStatics = [];

    public function setUp(): void
    {
        $this->savedContainer = Container::getInstance();
        $this->savedFacadeApplication = Facade::getFacadeApplication();
        $this->savedDispatcher = Model::getEventDispatcher();
        foreach ([[Facade::class, 'resolvedInstance'], [Model::class, 'booted'], [Model::class, 'eventsBooted']] as [$class, $name]) {
            $property = new ReflectionProperty($class, $name);
            $this->savedStatics[] = [$property, $property->getValue()];
            $property->setValue(null, []);
        }
        $resolver = new ReflectionProperty(Model::class, 'resolver');
        $this->savedStatics[] = [$resolver, $resolver->getValue()];

        $app = new Container;
        Container::setInstance($app);
        $app->instance('app', $app);
        Facade::setFacadeApplication($app);
        $this->capsule = new Manager($app);
        foreach (['default', 'other'] as $name) {
            $this->capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], $name);
        }
        $this->capsule->setEventDispatcher(new Dispatcher($app));
        $this->capsule->bootEloquent();
        $app->instance('db', $this->capsule->getDatabaseManager());

        foreach (['default', 'other'] as $name) {
            $schema = $this->capsule->getConnection($name)->getSchemaBuilder();
            $schema->create('translated_entries', function ($table) {
                $table->increments('id');
                $table->string('title');
                $table->text('settings');
                $table->text('metadata');
            });
            $schema->create('translate_attributes', function ($table) {
                $table->increments('id');
                $table->string('model_type');
                $table->integer('model_id');
                $table->string('locale');
                $table->string('attribute');
                $table->text('value')->nullable();
                $table->unique(['model_type', 'model_id', 'locale', 'attribute']);
            });
        }
    }

    public function tearDown(): void
    {
        TranslationBatchTestModel::$onFetched = null;
        TranslationBatchTestModel::$onFetching = null;
        TranslationBatchTestModel::$onNewInstance = null;
        if ($this->savedDispatcher) {
            Model::setEventDispatcher($this->savedDispatcher);
        }
        else {
            Model::unsetEventDispatcher();
        }
        Container::setInstance($this->savedContainer);
        Facade::setFacadeApplication($this->savedFacadeApplication);
        foreach ($this->savedStatics as [$property, $value]) {
            $property->setValue(null, $value);
        }
    }

    public function testCollectionTranslationQueriesStayBounded()
    {
        $this->seedEntries(100);
        foreach ([1, 25, 100] as $count) {
            $this->startQueryLog();
            $models = TranslationBatchTestModel::limit($count)->get();
            $this->assertSame(2, count($this->queries()));
            $this->assertSame(array_map(fn ($id) => 'French '.$id, range(1, $count)), $models->pluck('title')->all());
        }
    }

    public function testFetchedCallbacksSeeTranslationsInExistingEventOrder()
    {
        $this->seedEntries(2);
        $events = [];
        TranslationBatchTestModel::$onFetching = function () use (&$events) {
            $events[] = 'fetching';
        };
        TranslationBatchTestModel::$onFetched = function ($model) use (&$events) {
            $events[] = $model->title;
        };
        TranslationBatchTestModel::get();
        $this->assertSame(['fetching', 'French 1', 'fetching', 'French 2'], $events);
    }

    public function testLargeCollectionsBoundQueryParameters()
    {
        $this->seedEntries(1001);
        $this->startQueryLog();
        $models = TranslationBatchTestModel::get();
        $this->assertSame(array_map(fn ($id) => 'French '.$id, range(1, 1001)), $models->pluck('title')->all());
        $this->assertCount(4, $this->queries());
        foreach ($this->queries() as $query) {
            $this->assertLessThanOrEqual(502, count($query['bindings']));
        }
    }

    public function testDefaultLocaleDisabledAndEmptyResultsDoNotReadTranslations()
    {
        $this->seedEntries(3);
        foreach (['default', 'disabled', 'empty'] as $case) {
            $model = new TranslationBatchTestModel;
            if ($case === 'default') {
                $model->fixtureLocale = 'en';
            }
            if ($case === 'disabled') {
                $model->translationsEnabled = false;
            }
            $query = $model->newQuery();
            if ($case === 'empty') {
                $query->where('id', 0);
            }
            $this->startQueryLog();
            $models = $query->get();
            $this->assertCount(1, $this->queries());
            $this->assertSame($case === 'empty' ? [] : ['Base 1', 'Base 2', 'Base 3'], $models->pluck('title')->all());
        }
    }

    public function testMissingTranslationsAndAttributeConversionsKeepTheirBehavior()
    {
        $this->seedEntries(2);
        $this->db()->table('translate_attributes')->where('model_id', 2)->delete();
        $models = TranslationBatchTestModel::get();
        $this->assertSame(['French 1', 'Base 2'], $models->pluck('title')->all());
        $this->assertSame(['language' => 'fr'], $models[0]->settings);
        $this->assertSame(['language' => 'fr'], $models[0]->metadata);
        $this->assertSame(['language' => 'en'], $models[1]->settings);
        $this->assertSame(['language' => 'en'], $models[1]->metadata);
    }

    public function testEagerTranslationsRemainCompleteAcrossLocales()
    {
        $this->seedEntries(25);
        $this->startQueryLog();
        $models = TranslationBatchTestModel::with('translations')->get();
        $this->assertCount(3, $this->queries());
        $this->assertTrue($models[0]->relationLoaded('translations'));
        $this->assertCount(4, $models[0]->translations);
        $this->assertSame('German 1', $models[0]->setLocale('de')->title);
        $this->assertSame('French 1', $models[0]->setLocale('fr')->title);
        $this->assertCount(3, $this->queries());
    }

    public function testLocaleSwitchDirtyTrackingAndSaveRestoreBaseValues()
    {
        $this->seedEntries(2);
        $model = TranslationBatchTestModel::first();
        $this->assertFalse($model->isTranslateDirty());
        $this->assertSame('German 1', $model->setLocale('de')->title);
        $this->assertSame('Base 1', $model->setLocale('en')->title);
        $model->setLocale('fr');
        $model->title = 'Edited French';
        $this->assertTrue($model->save());
        $this->assertSame('Base 1', $this->db()->table('translated_entries')->where('id', 1)->value('title'));
        $this->assertSame('Edited French', $this->db()->table('translate_attributes')->where('model_id', 1)->where('locale', 'fr')->where('attribute', 'title')->value('value'));
        $this->assertSame('Base 1', $model->title);
        $this->assertSame('Edited French', TranslationBatchTestModel::find(1)->title);
    }

    public function testFirstChunkAndCursorKeepTranslationBehavior()
    {
        $this->seedEntries(5);
        $this->assertSame('French 1', TranslationBatchTestModel::first()->title);
        $titles = [];
        $this->startQueryLog();
        TranslationBatchTestModel::orderBy('id')->chunk(2, function ($models) use (&$titles) {
            array_push($titles, ...$models->pluck('title')->all());
        });
        $this->assertSame(['French 1', 'French 2', 'French 3', 'French 4', 'French 5'], $titles);
        $this->assertCount(6, $this->queries());
        $this->assertSame($titles, TranslationBatchTestModel::orderBy('id')->cursor()->map(fn ($model) => $model->title)->all());
    }

    public function testNestedHydrationDoesNotReplaceOuterBatch()
    {
        $this->seedEntries(3);
        $nested = null;
        TranslationBatchTestModel::$onFetched = function ($model) use (&$nested) {
            if ($model->id === 1 && $nested === null) {
                $nested = 'loading';
                $nested = TranslationBatchTestModel::find(3)->title;
            }
        };
        $this->startQueryLog();
        $models = TranslationBatchTestModel::whereIn('id', [1, 2])->get();
        $this->assertSame('French 3', $nested);
        $this->assertSame(['French 1', 'French 2'], $models->pluck('title')->all());
        $this->assertCount(4, $this->queries());
    }

    public function testFailedFetchDoesNotLeakBatchIntoLaterHydration()
    {
        $this->seedEntries(2);
        TranslationBatchTestModel::$onFetched = function () {
            throw new RuntimeException('Abort hydration');
        };
        try {
            TranslationBatchTestModel::get();
            $this->fail('Expected the fetch callback to throw.');
        }
        catch (RuntimeException $ex) {
            $this->assertSame('Abort hydration', $ex->getMessage());
        }
        TranslationBatchTestModel::$onFetched = null;
        $this->db()->table('translate_attributes')->where('model_id', 1)->where('attribute', 'title')->where('locale', 'fr')->update(['value' => 'Changed']);
        $this->assertSame('Changed', TranslationBatchTestModel::first()->title);
    }

    public function testNestedNewFromBuilderOutsideTheBatchKeepsItsLookup()
    {
        $this->seedEntries(3);
        $nested = null;
        TranslationBatchTestModel::$onFetched = function ($model) use (&$nested) {
            if ($model->id === 1 && $nested === null) {
                $nested = 'loading';
                $nested = $model->newFromBuilder(['id' => 3, 'title' => 'Base 3'])->title;
            }
        };
        $models = TranslationBatchTestModel::whereIn('id', [1, 2])->get();
        $this->assertSame('French 3', $nested);
        $this->assertSame(['French 1', 'French 2'], $models->pluck('title')->all());
    }

    public function testReturnedModelsDoNotRetainOtherLocaleBatchData()
    {
        $this->seedEntries(2);
        TranslationBatchTestModel::$onFetched = function ($model) {
            if ($model->id === 1) {
                $model->getTranslation('title', 'de');
            }
        };
        $models = TranslationBatchTestModel::get();
        $this->db()->table('translate_attributes')->where('model_id', 2)->where('locale', 'de')->update(['value' => 'Updated German']);
        $this->assertSame('Updated German', $models[1]->setLocale('de')->title);
    }

    public function testCustomTranslationLoaderRemainsInControl()
    {
        $this->seedEntries(2);
        $this->startQueryLog();
        $models = CustomTranslationLoaderTestModel::get();
        $this->assertSame(['Custom 1', 'Custom 2'], $models->pluck('title')->all());
        $this->assertCount(1, $this->queries());
    }

    public function testCustomTranslationTableAndMorphTypeStayIsolated()
    {
        $this->seedEntries(2);
        $this->db()->statement('create table custom_translations as select * from translate_attributes');
        $this->db()->table('custom_translations')->update(['model_type' => 'custom-entry']);
        $this->db()->table('custom_translations')->where('attribute', 'title')->where('locale', 'fr')->update(['value' => 'Custom table']);
        $this->startQueryLog();
        $models = CustomTranslationTableTestModel::get();
        $this->assertSame(['Custom table', 'Custom table'], $models->pluck('title')->all());
        $this->assertCount(2, $this->queries());
        $this->assertSame('French 1', TranslationBatchTestModel::first()->title);
    }

    public function testCancelledFetchingDoesNotLoadTranslations()
    {
        $this->seedEntries(2);
        TranslationBatchTestModel::$onFetching = fn () => false;
        $this->startQueryLog();
        $models = TranslationBatchTestModel::get();
        $this->assertSame([null, null], $models->pluck('title')->all());
        $this->assertCount(1, $this->queries());
    }

    public function testInstanceLocaleOverridesAndDisabledInstancesAreRespected()
    {
        $this->seedEntries(3);
        $index = 0;
        TranslationBatchTestModel::$onNewInstance = function ($model) use (&$index) {
            if (!$model->exists) {
                return;
            }
            $index++;
            $model->fixtureLocale = $index === 1 ? 'de' : 'fr';
            $model->translationsEnabled = $index !== 3;
        };
        $this->startQueryLog();
        $models = TranslationBatchTestModel::get();
        $this->assertSame(['German 1', 'French 2', 'Base 3'], $models->pluck('title')->all());
        $this->assertCount(3, $this->queries());
    }

    public function testSeparateModelConnectionKeepsDefaultTranslationStorage()
    {
        $this->seedEntries(2);
        $this->seedEntries(2, 'other', 'Other French ');
        $this->startQueryLog('default');
        $this->startQueryLog('other');
        $models = TranslationBatchTestModel::on('other')->get();
        $this->assertSame(['French 1', 'French 2'], $models->pluck('title')->all());
        $this->assertCount(1, $this->queries());
        $this->assertCount(1, $this->queries('other'));
        $raw = (object) ['id' => 1, 'title' => 'Base 1'];
        $model = (new TranslationBatchTestModel)->newFromBuilder($raw, 'other');
        $this->assertSame('French 1', $model->title);
    }

    public function testMissingSelectedKeyDoesNotTranslateAnUnrelatedRow()
    {
        $this->seedEntries(2);
        $this->startQueryLog();
        $models = TranslationBatchTestModel::get(['title']);
        $this->assertSame(['Base 1', 'Base 2'], $models->pluck('title')->all());
        $this->assertCount(1, $this->queries());
    }

    protected function seedEntries($count, $connection = 'default', $prefix = 'French ')
    {
        for ($id = 1; $id <= $count; $id++) {
            $this->db($connection)->table('translated_entries')->insert([
                'id' => $id,
                'title' => 'Base '.$id,
                'settings' => '{"language":"en"}',
                'metadata' => '{"language":"en"}'
            ]);
            foreach (['fr' => ['title' => $prefix.$id, 'settings' => '{"language":"fr"}', 'metadata' => '{"language":"fr"}'], 'de' => ['title' => 'German '.$id]] as $locale => $values) {
                foreach ($values as $attribute => $value) {
                    $this->db($connection)->table('translate_attributes')->insert([
                        'model_type' => TranslationBatchTestModel::class,
                        'model_id' => $id,
                        'locale' => $locale,
                        'attribute' => $attribute,
                        'value' => $value
                    ]);
                }
            }
        }
    }

    protected function db($connection = 'default')
    {
        return $this->capsule->getConnection($connection);
    }

    protected function startQueryLog($connection = 'default')
    {
        $this->db($connection)->flushQueryLog();
        $this->db($connection)->enableQueryLog();
    }

    protected function queries($connection = 'default')
    {
        return $this->db($connection)->getQueryLog();
    }
}

class TranslationBatchTestModel extends Model
{
    use \October\Rain\Database\Traits\Translatable;

    public static $onFetched;
    public static $onFetching;
    public static $onNewInstance;
    public $translatable = ['title', 'settings', 'metadata'];
    public $timestamps = false;
    public $fixtureLocale = 'fr';
    public $translationsEnabled = true;
    protected $table = 'translated_entries';
    protected $jsonable = ['settings'];
    protected $casts = ['metadata' => 'array'];

    public function afterInit()
    {
        $this->bindEvent('model.newInstance', function ($model) {
            $model->fixtureLocale = $this->fixtureLocale;
            $model->translationsEnabled = $this->translationsEnabled;
            if (self::$onNewInstance) {
                (self::$onNewInstance)($model);
            }
        });
    }

    public function beforeFetch()
    {
        if (self::$onFetching) {
            return (self::$onFetching)($this);
        }
    }

    public function afterFetch()
    {
        if (self::$onFetched) {
            (self::$onFetched)($this);
        }
    }

    public function isTranslatableEnabled()
    {
        return $this->translationsEnabled;
    }

    protected function resolveTranslatableLocale()
    {
        return $this->fixtureLocale;
    }

    protected function resolveTranslatableDefaultLocale()
    {
        return 'en';
    }
}

class CustomTranslationLoaderTestModel extends TranslationBatchTestModel
{
    protected function loadTranslatableData($locale)
    {
        $this->translatableAttributes[$locale] = ['title' => 'Custom '.$this->getKey()];
        $this->translatableOriginals[$locale] = $this->translatableAttributes[$locale];
    }
}

class CustomTranslationTableTestModel extends TranslationBatchTestModel
{
    public function getTranslateAttributeTable()
    {
        return 'custom_translations';
    }

    public function getMorphClass()
    {
        return 'custom-entry';
    }
}
