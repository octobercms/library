<?php

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Facade;
use October\Rain\Database\Model;

class TranslationBatchTest extends TestCase
{
    protected $capsule;
    protected $db;
    protected $savedFacadeApplication;
    protected $savedResolver;
    protected $savedDispatcher;
    protected $savedStatics = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->savedFacadeApplication = Facade::getFacadeApplication();
        $this->savedResolver = Model::getConnectionResolver();
        $this->savedDispatcher = Model::getEventDispatcher();
        foreach (['booted', 'eventsBooted'] as $name) {
            $property = new ReflectionProperty(Model::class, $name);
            $this->savedStatics[] = [$property, $property->getValue()];
            $property->setValue(null, []);
        }

        $app = new Container;
        $app->instance('app', $app);
        $capsule = new Manager($app);
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->setEventDispatcher(new Dispatcher($app));
        $capsule->bootEloquent();
        $app->instance('db', $capsule->getDatabaseManager());
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);

        $this->capsule = $capsule;
        $this->db = $capsule->getConnection();
        $this->db->getSchemaBuilder()->create('translated_entries', function ($table) {
            $table->increments('id');
            $table->string('title');
        });
        $this->db->getSchemaBuilder()->create('translate_attributes', function ($table) {
            $table->increments('id');
            $table->string('model_type');
            $table->integer('model_id');
            $table->string('locale');
            $table->string('attribute');
            $table->text('value')->nullable();
            $table->unique(['model_type', 'model_id', 'locale', 'attribute']);
        });
    }

    public function tearDown(): void
    {
        TranslationBatchTestModel::$locale = 'fr';
        TranslationBatchTestModel::$onFetched = null;
        if ($this->savedResolver) {
            Model::setConnectionResolver($this->savedResolver);
        }
        else {
            Model::unsetConnectionResolver();
        }
        if ($this->savedDispatcher) {
            Model::setEventDispatcher($this->savedDispatcher);
        }
        else {
            Model::unsetEventDispatcher();
        }
        foreach ($this->savedStatics as [$property, $value]) {
            $property->setValue(null, $value);
        }
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->savedFacadeApplication);
        parent::tearDown();
    }

    public function testFetchedModelsShareOneTranslationQuery()
    {
        $this->seedEntries(3);
        $seen = [];
        TranslationBatchTestModel::$onFetched = function ($model) use (&$seen) {
            $seen[] = $model->title;
        };

        $this->db->enableQueryLog();
        $models = TranslationBatchTestModel::get();

        $this->assertSame(['French 1', 'French 2', 'French 3'], $models->pluck('title')->all());
        $this->assertSame(['French 1', 'French 2', 'French 3'], $seen);
        $this->assertCount(2, $this->db->getQueryLog());
    }

    public function testTranslationQueriesAreChunked()
    {
        $this->seedEntries(501);

        $this->db->enableQueryLog();
        $models = TranslationBatchTestModel::get();

        $this->assertSame('French 1', $models->first()->title);
        $this->assertSame('French 501', $models->last()->title);
        $this->assertCount(3, $this->db->getQueryLog());
        foreach ($this->db->getQueryLog() as $query) {
            $this->assertLessThanOrEqual(502, count($query['bindings']));
        }
    }

    public function testRowsWithoutTranslationsKeepBaseValuesWithoutQuerying()
    {
        $this->seedEntries(2);
        $this->db->table('translate_attributes')->where('model_id', 2)->delete();

        $this->db->enableQueryLog();
        $models = TranslationBatchTestModel::get();

        $this->assertSame(['French 1', 'Base 2'], $models->pluck('title')->all());
        $this->assertCount(2, $this->db->getQueryLog());
    }

    public function testDefaultLocaleDoesNotQueryTranslations()
    {
        $this->seedEntries(2);
        TranslationBatchTestModel::$locale = 'en';

        $this->db->enableQueryLog();
        $models = TranslationBatchTestModel::get();

        $this->assertSame(['Base 1', 'Base 2'], $models->pluck('title')->all());
        $this->assertCount(1, $this->db->getQueryLog());
    }

    public function testEagerLoadedTranslationsServeOtherLocales()
    {
        $this->seedEntries(2);

        $this->db->enableQueryLog();
        $model = TranslationBatchTestModel::with('translations')->get()->first();

        $this->assertSame('French 1', $model->title);
        $this->assertSame('German 1', $model->setLocale('de')->title);
        $this->assertCount(3, $this->db->getQueryLog());
    }

    public function testPreloadedTranslationsAreCleanAndSaveAsTranslations()
    {
        $this->seedEntries(1);
        $model = TranslationBatchTestModel::first();

        $this->assertFalse($model->isTranslateDirty());

        $model->title = 'Edited French';
        $model->save();

        $this->assertSame('Base 1', $this->db->table('translated_entries')->value('title'));
        $this->assertSame('Edited French', TranslationBatchTestModel::first()->title);
    }

    public function testNestedQueriesInFetchedCallbacksKeepTheOuterBatch()
    {
        $this->seedEntries(3);
        $nested = null;
        TranslationBatchTestModel::$onFetched = function ($model) use (&$nested) {
            if ($model->id === 1 && $nested === null) {
                $nested = 'loading';
                $nested = TranslationBatchTestModel::find(3)->title;
            }
        };

        $this->db->enableQueryLog();
        $models = TranslationBatchTestModel::whereIn('id', [1, 2])->get();

        $this->assertSame('French 3', $nested);
        $this->assertSame(['French 1', 'French 2'], $models->pluck('title')->all());
        $this->assertCount(4, $this->db->getQueryLog());
    }

    public function testLazyLoadingPreventionMatchesEloquent()
    {
        $this->seedEntries(2);
        $previous = Model::preventsLazyLoading();
        Model::preventLazyLoading();

        try {
            $models = TranslationBatchTestModel::get();
            $this->assertTrue($models[0]->preventsLazyLoading);
            $this->assertTrue($models[1]->preventsLazyLoading);
            $this->assertFalse(TranslationBatchTestModel::first()->preventsLazyLoading);
        }
        finally {
            Model::preventLazyLoading($previous);
        }
    }

    public function testRowsWithoutTheirKeyAreNotTranslated()
    {
        $this->seedEntries(2);

        $this->db->enableQueryLog();
        $models = TranslationBatchTestModel::get(['title']);

        $this->assertSame(['Base 1', 'Base 2'], $models->pluck('title')->all());
        $this->assertCount(1, $this->db->getQueryLog());
    }

    public function testDecoratingLoaderStillRunsForEachModel()
    {
        $this->seedEntries(2);

        $this->db->enableQueryLog();
        $models = DecoratingLoaderTestModel::get();

        $this->assertSame(['Decoded: French 1', 'Decoded: French 2'], $models->pluck('title')->all());
        $this->assertFalse($models->first()->isTranslateDirty());
        $this->assertCount(3, $this->db->getQueryLog());
    }

    public function testReplacementLoaderDoesNotNeedTheTranslationTable()
    {
        $this->seedEntries(2);
        $this->db->getSchemaBuilder()->drop('translate_attributes');

        $models = ReplacementLoaderTestModel::get();

        $this->assertSame(['External 1', 'External 2'], $models->pluck('title')->all());
    }

    public function testPerRecordTranslationTableIsResolvedForEachModel()
    {
        $this->seedEntries(2);
        $this->db->table('translated_entries')->where('id', 2)->update(['title' => 'Tenant 2']);
        $this->db->statement('create table tenant_translations as select * from translate_attributes');
        $this->db->table('tenant_translations')->update(['value' => 'Tenant French']);

        $models = PerRecordTableTestModel::get();

        $this->assertSame(['French 1', 'Tenant French'], $models->pluck('title')->all());
    }

    public function testPerRecordMorphClassReadsItsOwnTranslations()
    {
        $this->seedEntries(2);
        $this->db->table('translated_entries')->where('id', 2)->update(['title' => 'Other 2']);
        $this->db->table('translate_attributes')->insert([
            'model_type' => 'other-entry',
            'model_id' => 2,
            'locale' => 'fr',
            'attribute' => 'title',
            'value' => 'Other French',
        ]);

        $models = PerRecordMorphTestModel::get();

        $this->assertSame(['French 1', 'Other French'], $models->pluck('title')->all());
    }

    public function testMorphAliasIsUsedForTheBatch()
    {
        $this->seedEntries(2);
        $this->db->table('translate_attributes')->update(['model_type' => 'entry-alias']);

        $this->db->enableQueryLog();
        $models = MorphAliasTestModel::get();

        $this->assertSame(['French 1', 'French 2'], $models->pluck('title')->all());
        $this->assertCount(2, $this->db->getQueryLog());
    }

    public function testTranslationsAreReadFromTheDefaultConnectionLikeTheLoader()
    {
        $this->seedEntries(2);
        $this->capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:'], 'other');
        $other = $this->capsule->getConnection('other');
        $other->getSchemaBuilder()->create('translated_entries', function ($table) {
            $table->increments('id');
            $table->string('title');
        });
        $other->table('translated_entries')->insert([['id' => 1, 'title' => 'Base 1'], ['id' => 2, 'title' => 'Base 2']]);

        $models = TranslationBatchTestModel::on('other')->get();

        $this->assertSame(['French 1', 'French 2'], $models->pluck('title')->all());
    }

    protected function seedEntries($count)
    {
        $entries = $translations = [];
        for ($id = 1; $id <= $count; $id++) {
            $entries[] = ['id' => $id, 'title' => 'Base '.$id];
            foreach (['fr' => 'French ', 'de' => 'German '] as $locale => $prefix) {
                $translations[] = [
                    'model_type' => TranslationBatchTestModel::class,
                    'model_id' => $id,
                    'locale' => $locale,
                    'attribute' => 'title',
                    'value' => $prefix.$id,
                ];
            }
        }

        foreach (array_chunk($entries, 200) as $chunk) {
            $this->db->table('translated_entries')->insert($chunk);
        }
        foreach (array_chunk($translations, 100) as $chunk) {
            $this->db->table('translate_attributes')->insert($chunk);
        }
    }
}

class TranslationBatchTestModel extends Model
{
    use \October\Rain\Database\Traits\Translatable;

    public static $locale = 'fr';

    public static $onFetched;

    public $translatable = ['title'];

    public $timestamps = false;

    protected $table = 'translated_entries';

    public function afterFetch()
    {
        if (static::$onFetched) {
            (static::$onFetched)($this);
        }
    }

    public function getMorphClass()
    {
        return TranslationBatchTestModel::class;
    }

    protected function resolveTranslatableLocale()
    {
        return static::$locale;
    }

    protected function resolveTranslatableDefaultLocale()
    {
        return 'en';
    }
}

class DecoratingLoaderTestModel extends TranslationBatchTestModel
{
    protected function loadTranslatableData($locale)
    {
        parent::loadTranslatableData($locale);

        if (isset($this->translatableAttributes[$locale]['title'])) {
            $title = 'Decoded: '.$this->translatableAttributes[$locale]['title'];
            $this->translatableAttributes[$locale]['title'] = $title;
            $this->translatableOriginals[$locale]['title'] = $title;
        }
    }
}

class ReplacementLoaderTestModel extends TranslationBatchTestModel
{
    protected function loadTranslatableData($locale)
    {
        $this->translatableAttributes[$locale] = ['title' => 'External '.$this->getKey()];
        $this->translatableOriginals[$locale] = $this->translatableAttributes[$locale];
    }
}

class PerRecordTableTestModel extends TranslationBatchTestModel
{
    public function getTranslateAttributeTable()
    {
        return str_starts_with((string) $this->getRawOriginal('title'), 'Tenant')
            ? 'tenant_translations'
            : 'translate_attributes';
    }
}

class PerRecordMorphTestModel extends TranslationBatchTestModel
{
    public function getMorphClass()
    {
        return str_starts_with((string) $this->getRawOriginal('title'), 'Other')
            ? 'other-entry'
            : TranslationBatchTestModel::class;
    }
}

class MorphAliasTestModel extends TranslationBatchTestModel
{
    public function getMorphClass()
    {
        return 'entry-alias';
    }
}
