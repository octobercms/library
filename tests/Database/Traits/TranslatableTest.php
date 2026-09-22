<?php

/**
 * TranslatableTest
 */
class TranslatableTest extends TestCase
{
    /**
     * @var Illuminate\Database\Capsule\Manager capsule shared across tests so a single
     * :memory: connection is reused, keeping table drops and creates reliable
     */
    protected static $capsule;

    /**
     * @var mixed savedFacadeApp
     */
    protected $savedFacadeApp;

    /**
     * setUp test
     */
    public function setUp(): void
    {
        if (!self::$capsule) {
            self::$capsule = new Illuminate\Database\Capsule\Manager;
            self::$capsule->addConnection([
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => ''
            ]);

            self::$capsule->setEventDispatcher(new Illuminate\Events\Dispatcher);
            self::$capsule->setAsGlobal();
            self::$capsule->bootEloquent();
        }

        $capsule = self::$capsule;

        // Wire a real container as the facade root so the Db and App facades resolve
        $this->savedFacadeApp = Illuminate\Support\Facades\Facade::getFacadeApplication();

        $app = new Illuminate\Container\Container;
        $app->singleton('db', fn () => $capsule->getDatabaseManager());
        $app->instance('app', $app);
        Illuminate\Support\Facades\Facade::clearResolvedInstances();
        Illuminate\Support\Facades\Facade::setFacadeApplication($app);

        // Re-register model events against the dispatcher so afterFetch fires
        TestModelTranslatable::flushEventListeners();

        $capsule->schema()->dropIfExists('test_translatable');
        $capsule->schema()->dropIfExists('translate_attributes');

        $capsule->schema()->create('test_translatable', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        $capsule->schema()->create('translate_attributes', function ($table) {
            $table->increments('id');
            $table->string('model_type');
            $table->integer('model_id');
            $table->string('locale');
            $table->string('attribute');
            $table->text('value')->nullable();
            $table->unique(['model_type', 'model_id', 'locale', 'attribute']);
        });

        TestModelTranslatable::$activeLocale = 'en';
    }

    /**
     * tearDown test
     */
    public function tearDown(): void
    {
        Illuminate\Support\Facades\Facade::setFacadeApplication($this->savedFacadeApp);
    }

    /**
     * testDefaultLocaleReadsBaseValue confirms the default locale reads straight from $attributes
     */
    public function testDefaultLocaleReadsBaseValue()
    {
        $model = TestModelTranslatable::create(['name' => 'Product']);

        $this->assertEquals('Product', $model->name);
        $this->assertEquals('Product', $model->getTranslation('name', 'en'));
    }

    /**
     * testSetAndGetTranslation stores and reads a non-default locale value
     */
    public function testSetAndGetTranslation()
    {
        $model = TestModelTranslatable::create(['name' => 'Product']);
        $model->setTranslation('name', 'fr', 'Produit');
        $model->save();

        $fresh = TestModelTranslatable::find($model->id);

        $this->assertEquals('Product', $fresh->getTranslation('name', 'en'));
        $this->assertEquals('Produit', $fresh->getTranslation('name', 'fr'));
    }

    /**
     * testFallbackToDefaultLocale confirms an untranslated attribute falls back to the default value
     */
    public function testFallbackToDefaultLocale()
    {
        $model = TestModelTranslatable::create(['name' => 'Product']);
        $model->save();

        $this->assertEquals('Product', $model->getTranslation('name', 'fr'));
        $this->assertNull($model->getTranslation('name', 'fr', false));
    }

    /**
     * testActiveLocalePromotesTranslatedValue confirms attribute access follows the active locale
     */
    public function testActiveLocalePromotesTranslatedValue()
    {
        $model = TestModelTranslatable::create(['name' => 'Product']);
        $model->setTranslation('name', 'fr', 'Produit');
        $model->save();

        TestModelTranslatable::$activeLocale = 'fr';
        $fresh = TestModelTranslatable::find($model->id);

        $this->assertEquals('Produit', $fresh->name);
    }

    /**
     * testGetTranslationsReturnsAllLocales confirms getTranslations lists every locale for one attribute
     */
    public function testGetTranslationsReturnsAllLocales()
    {
        $model = TestModelTranslatable::create(['name' => 'Product']);
        $model->setTranslation('name', 'fr', 'Produit');
        $model->setTranslation('name', 'de', 'Produkt');
        $model->save();

        $fresh = TestModelTranslatable::find($model->id);
        $translations = $fresh->getTranslations('name');

        $this->assertEquals('Product', $translations['en']);
        $this->assertEquals('Produit', $translations['fr']);
        $this->assertEquals('Produkt', $translations['de']);
    }

    public function testRepeatedActiveLocaleSavesPreserveBaseLanguage()
    {
        $model = $this->makeFrenchModel();
        $model->name = 'Première modification';
        $model->save();
        $this->assertSame('Première modification', $model->name);
        $this->assertFalse($model->isTranslateDirty());

        $model->name = 'Deuxième modification';
        $model->save();
        $this->assertSame('Product', Db::table('test_translatable')->where('id', $model->id)->value('name'));
        $this->assertSame('Deuxième modification', TestModelTranslatable::find($model->id)->name);
    }

    public function testSuccessfulSavesSyncOriginalsAndDoNotRewriteTranslations()
    {
        $model = TestModelTranslatable::create(['name' => 'Product']);
        $model->setTranslation('name', 'fr', 'Produit');
        $events = [];
        $model->bindEvent('model.translate.beforeSave', function ($locale) use (&$events) { $events[] = 'before:'.$locale; });
        $model->bindEvent('model.translate.afterSave', function ($locale) use (&$events) { $events[] = 'after:'.$locale; });
        $model->save();
        $this->assertFalse($model->isTranslateDirty(null, 'fr'));
        $this->assertSame(['before:fr', 'after:fr'], $events);

        Db::connection()->flushQueryLog();
        Db::connection()->enableQueryLog();
        $model->save();
        $writes = array_filter(Db::connection()->getQueryLog(), fn ($query) =>
            str_contains($query['query'], 'translate_attributes') &&
            preg_match('/^(insert|update|delete)/i', $query['query'])
        );
        $this->assertCount(0, $writes);
        $this->assertSame(['before:fr', 'after:fr'], $events);
    }

    public function testCancelledSaveRetainsActiveEditsWithoutPersistingTranslations()
    {
        $model = $this->makeFrenchModel();
        $model->name = 'Modification';
        $cancel = true;
        $model->bindEvent('model.saveInternal', function () use (&$cancel) { return $cancel ? false : null; }, -10);
        $this->assertFalse($model->save());
        $this->assertSame('Modification', $model->name);
        $this->assertSame('Produit', TestModelTranslatable::find($model->id)->name);
        $this->assertTrue($model->isTranslateDirty());

        $cancel = false;
        $model->save();
        $this->assertSame('Modification', TestModelTranslatable::find($model->id)->name);
        $this->assertSame('Product', $model->getTranslation('name', 'en'));
    }

    public function testThrowingSaveRetainsActiveEditsForRetry()
    {
        $model = $this->makeFrenchModel();
        $model->name = 'Modification';
        $model->bindEvent('model.beforeSave', function () { throw new RuntimeException('Save failed'); });
        try {
            $model->save();
            $this->fail('Expected a save exception');
        }
        catch (RuntimeException $exception) {
            $this->assertSame('Save failed', $exception->getMessage());
        }
        $this->assertSame('Modification', $model->name);
        $this->assertSame('Produit', TestModelTranslatable::find($model->id)->name);
        $this->assertTrue($model->isTranslateDirty());
        $model->unbindEvent('model.beforeSave');
        $model->save();
        $this->assertSame('Modification', TestModelTranslatable::find($model->id)->name);
    }

    public function testNewRecordTranslationsAreSavedAfterKeyAssignmentAndCanRetry()
    {
        $model = new TestModelTranslatable(['name' => 'Product']);
        $model->setTranslation('name', 'fr', 'Produit');
        $cancel = true;
        $model->bindEvent('model.saveInternal', function () use (&$cancel) { return $cancel ? false : null; }, -10);
        $this->assertFalse($model->save());
        $this->assertNull($model->id);
        $this->assertSame(0, Db::table('translate_attributes')->count());
        $this->assertTrue($model->isTranslateDirty(null, 'fr'));

        $cancel = false;
        $model->save();
        $this->assertSame('Product', $model->name);
        $this->assertFalse($model->isTranslateDirty(null, 'fr'));
        $this->assertSame($model->id, Db::table('translate_attributes')->value('model_id'));
        $this->assertSame('Produit', TestModelTranslatable::find($model->id)->getTranslation('name', 'fr'));
    }

    public function testResettingExistingTranslationToBaseRemovesOverride()
    {
        foreach (['en', 'fr'] as $locale) {
            $model = $this->makeFrenchModel();
            $model->setLocale($locale);
            $model->setTranslation('name', 'fr', 'Product');
            $model->save();
            $this->assertFalse($model->isTranslateDirty(null, 'fr'));
            $this->assertSame(0, Db::table('translate_attributes')->where('model_id', $model->id)->count());
            $fresh = TestModelTranslatable::find($model->id);
            $this->assertSame('Product', $fresh->name);
            $fresh->setTranslation('name', 'en', 'New base');
            $fresh->save();
            $this->assertSame('New base', TestModelTranslatable::find($model->id)->name);
        }
    }

    public function testForgettingActiveTranslationsDoesNotResurrectThemOnSave()
    {
        foreach (['forgetTranslation', 'forgetTranslations', 'forgetAllTranslations'] as $method) {
            $model = $this->makeFrenchModel();
            $model->load('translations');
            if ($method === 'forgetTranslation') {
                $model->$method('name', 'fr');
            }
            elseif ($method === 'forgetTranslations') {
                $model->$method('name');
            }
            else {
                $model->$method('fr');
            }
            $this->assertSame('Product', $model->name, $method);
            $this->assertFalse($model->hasTranslations('fr'), $method);
            $model->description = 'Unrelated edit';
            $model->save();
            $this->assertSame(0, Db::table('translate_attributes')->where('model_id', $model->id)->where('attribute', 'name')->count(), $method);
            $this->assertSame('Product', TestModelTranslatable::find($model->id)->name, $method);
        }
    }

    public function testForgettingAnotherLocalePreservesActiveEdits()
    {
        $model = $this->makeFrenchModel();
        $model->setTranslation('name', 'de', 'Produkt');
        $model->save();
        $model->name = 'Modification';
        $model->forgetAllTranslations('de');
        $this->assertSame('Modification', $model->name);
        $model->save();
        $this->assertSame('Modification', TestModelTranslatable::find($model->id)->name);
        $this->assertFalse($model->hasTranslations('de'));
    }

    public function testEloquentCancellationDoesNotWriteTranslations()
    {
        $model = $this->makeFrenchModel();
        $model->name = 'Modification';
        $cancel = true;
        TestModelTranslatable::saving(function () use (&$cancel) { return $cancel ? false : null; });
        $this->assertFalse($model->save());
        $this->assertSame('Modification', $model->name);
        $this->assertSame('Produit', TestModelTranslatable::find($model->id)->name);
        $cancel = false;
        $model->save();
        $this->assertSame('Modification', TestModelTranslatable::find($model->id)->name);
    }

    public function testFailedTranslationWriteRemainsDirtyAndCanRetry()
    {
        $model = $this->makeFrenchModel();
        $model->name = 'Modification';
        $model->bindEvent('model.translate.beforeSave', function () { throw new RuntimeException('Translation failed'); });
        try {
            $model->save();
            $this->fail('Expected a translation exception');
        }
        catch (RuntimeException $exception) {
            $this->assertSame('Translation failed', $exception->getMessage());
        }
        $this->assertSame('Modification', $model->name);
        $this->assertTrue($model->isTranslateDirty());
        $this->assertSame('Produit', TestModelTranslatable::find($model->id)->name);
        $model->unbindEvent('model.translate.beforeSave');
        $model->save();
        $this->assertSame('Modification', TestModelTranslatable::find($model->id)->name);
        $this->assertFalse($model->isTranslateDirty());
    }

    public function testNewActiveLocaleRecordRemainsEditableWithoutChangingBase()
    {
        TestModelTranslatable::$activeLocale = 'fr';
        $model = new TestModelTranslatable(['name' => 'Product']);
        $model->save();
        $this->assertSame('Product', $model->name);
        $this->assertFalse($model->isTranslateDirty());
        $model->name = 'Produit';
        $model->save();
        $this->assertSame('Product', $model->getTranslation('name', 'en'));
        $this->assertSame('Produit', TestModelTranslatable::find($model->id)->name);
    }

    public function testForgettingOneActiveAttributePreservesOtherEdits()
    {
        $model = $this->makeFrenchModel();
        $model->description = 'Description française';
        $model->setTranslation('name', 'de', 'Produkt');
        $model->forgetTranslation('name', 'fr');
        $model->save();
        $this->assertSame('Product', $model->name);
        $this->assertSame('Description française', $model->description);
        $this->assertSame('Produkt', $model->getTranslation('name', 'de'));
        $this->assertFalse($model->isTranslateDirty(null, 'fr'));

        Db::connection()->flushQueryLog();
        Db::connection()->enableQueryLog();
        $model->save();
        $writes = array_filter(Db::connection()->getQueryLog(), fn ($query) =>
            str_contains($query['query'], 'translate_attributes') &&
            preg_match('/^(insert|update|delete)/i', $query['query'])
        );
        $this->assertCount(0, $writes);
    }

    protected function makeFrenchModel()
    {
        TestModelTranslatable::$activeLocale = 'en';
        $model = TestModelTranslatable::create(['name' => 'Product']);
        $model->setTranslation('name', 'fr', 'Produit');
        $model->save();
        TestModelTranslatable::$activeLocale = 'fr';
        return TestModelTranslatable::find($model->id);
    }

    //
    // Query scopes
    //

    /**
     * testTransOrderBySortsByTranslatedValue confirms transOrderBy sorts on the locale value
     */
    public function testTransOrderBySortsByTranslatedValue()
    {
        $alpha = TestModelTranslatable::create(['name' => 'Alpha']);
        $alpha->setTranslation('name', 'fr', 'Zulu');
        $alpha->save();

        $zeta = TestModelTranslatable::create(['name' => 'Zeta']);
        $zeta->setTranslation('name', 'fr', 'Alpha');
        $zeta->save();

        // Default locale sorts by the base column
        $enOrder = TestModelTranslatable::transOrderBy('name', 'asc', 'en')->pluck('name')->all();
        $this->assertEquals(['Alpha', 'Zeta'], $enOrder);

        // French locale sorts by the translated value, reversing the order
        $frOrder = TestModelTranslatable::transOrderBy('name', 'asc', 'fr')->pluck('name')->all();
        $this->assertEquals(['Zeta', 'Alpha'], $frOrder);
    }

    /**
     * testTransOrderByFallsBackToBaseValue confirms untranslated rows sort by their base value
     */
    public function testTransOrderByFallsBackToBaseValue()
    {
        $translated = TestModelTranslatable::create(['name' => 'Bravo']);
        $translated->setTranslation('name', 'fr', 'Mike');
        $translated->save();

        // No French translation, so it should sort using the base value "Alpha"
        $untranslated = TestModelTranslatable::create(['name' => 'Alpha']);
        $untranslated->save();

        $frOrder = TestModelTranslatable::transOrderBy('name', 'asc', 'fr')->pluck('name')->all();

        $this->assertEquals(['Alpha', 'Bravo'], $frOrder);
    }

    //
    // Deprecated aliases
    //

    /**
     * testGetAttributeTranslatedAlias confirms the deprecated getter delegates to getTranslation
     */
    public function testGetAttributeTranslatedAlias()
    {
        $model = TestModelTranslatable::create(['name' => 'Product', 'description' => 'A product']);
        $model->setTranslation('name', 'fr', 'Produit');
        $model->save();

        // Translated attribute returns its locale value
        $this->assertEquals('Produit', $model->getAttributeTranslated('name', 'fr'));

        // Untranslated attribute falls back to the default value, or null with fallback off
        $this->assertEquals('A product', $model->getAttributeTranslated('description', 'fr'));
        $this->assertNull($model->getAttributeTranslated('description', 'fr', false));
    }

    /**
     * testSetAttributeTranslatedAlias confirms the deprecated setter uses the old argument order
     */
    public function testSetAttributeTranslatedAlias()
    {
        $model = TestModelTranslatable::create(['name' => 'Product']);
        $model->setAttributeTranslated('name', 'Produit', 'fr');
        $model->save();

        $this->assertEquals('Produit', $model->getTranslation('name', 'fr'));
    }

    /**
     * testGetTranslateAttributesAlias confirms the deprecated helper returns every attribute for a locale
     */
    public function testGetTranslateAttributesAlias()
    {
        $model = TestModelTranslatable::create(['name' => 'Product', 'description' => 'A product']);
        $model->setTranslation('name', 'fr', 'Produit');
        $model->setTranslation('description', 'fr', 'Un produit');
        $model->save();

        $data = $model->getTranslateAttributes('fr');

        $this->assertEquals(['name' => 'Produit', 'description' => 'Un produit'], $data);
    }

    /**
     * testTranslateContextAlias confirms the deprecated combined getter/setter
     */
    public function testTranslateContextAlias()
    {
        $model = TestModelTranslatable::create(['name' => 'Product']);

        $this->assertEquals('en', $model->translateContext());

        $model->translateContext('fr');
        $this->assertEquals('fr', $model->getLocale());
    }

    /**
     * testLangAlias confirms the deprecated chainable locale setter
     */
    public function testLangAlias()
    {
        $model = TestModelTranslatable::create(['name' => 'Product']);

        $this->assertSame($model, $model->lang('fr'));
        $this->assertEquals('fr', $model->getLocale());
    }

    /**
     * testIsTranslatableAlias confirms the deprecated attribute check
     */
    public function testIsTranslatableAlias()
    {
        TestModelTranslatable::$activeLocale = 'fr';
        $model = TestModelTranslatable::create(['name' => 'Product']);

        $this->assertTrue($model->isTranslatable('name'));
        $this->assertFalse($model->isTranslatable('id'));
    }
}

/**
 * TestModelTranslatable example class
 */
class TestModelTranslatable extends Model
{
    use \October\Rain\Database\Traits\Translatable;

    public static $activeLocale = 'en';

    public $translatable = ['name', 'description'];

    protected $fillable = ['name', 'description'];

    protected $table = 'test_translatable';

    protected function resolveTranslatableLocale()
    {
        return static::$activeLocale;
    }

    protected function resolveTranslatableDefaultLocale()
    {
        return 'en';
    }
}