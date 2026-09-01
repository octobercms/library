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