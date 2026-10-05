<?php

use October\Rain\Database\Attach\File;
use October\Rain\Database\Relations\AttachOne;
use October\Rain\Database\Relations\TranslatableAttachOne;
use October\Rain\Database\Relations\TranslatableAttachMany;

/**
 * TranslatableAttachmentTest
 */
class TranslatableAttachmentTest extends TestCase
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
        }

        $capsule = self::$capsule;
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // Wire a real container as the facade root so the Db, App and Event facades resolve
        $this->savedFacadeApp = Illuminate\Support\Facades\Facade::getFacadeApplication();

        $app = new Illuminate\Container\Container;
        $app->singleton('db', fn () => $capsule->getDatabaseManager());
        $app->singleton('events', fn () => $capsule->getEventDispatcher());
        $app->instance('app', $app);
        Illuminate\Support\Facades\Facade::clearResolvedInstances();
        Illuminate\Support\Facades\Facade::setFacadeApplication($app);

        // Re-register model events against the dispatcher so afterFetch fires
        TestModelTranslatableAttachment::flushEventListeners();
        File::flushEventListeners();

        foreach (['test_translatable_attachments', 'translate_attributes', 'files', 'deferred_bindings'] as $table) {
            $capsule->schema()->dropIfExists($table);
        }

        $capsule->schema()->create('test_translatable_attachments', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
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

        $capsule->schema()->create('files', function ($table) {
            $table->increments('id');
            $table->string('disk_name')->nullable();
            $table->string('file_name')->nullable();
            $table->integer('file_size')->nullable();
            $table->string('content_type')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('field')->nullable();
            $table->string('attachment_id')->nullable();
            $table->string('attachment_type')->nullable();
            $table->boolean('is_public')->default(true);
            $table->integer('sort_order')->nullable();
            $table->timestamps();
        });

        $capsule->schema()->create('deferred_bindings', function ($table) {
            $table->increments('id');
            $table->string('master_type');
            $table->string('master_field');
            $table->string('slave_type');
            $table->integer('slave_id');
            $table->mediumText('pivot_data')->nullable();
            $table->string('session_key');
            $table->boolean('is_bind')->default(true);
            $table->timestamps();
        });

        TestModelTranslatableAttachment::$activeLocale = 'en';
    }

    /**
     * tearDown test
     */
    public function tearDown(): void
    {
        Illuminate\Support\Facades\Facade::setFacadeApplication($this->savedFacadeApp);
    }

    /**
     * testRelationsAreSwappedForTranslatableAttachments confirms only listed attachments get locale relations
     */
    public function testRelationsAreSwappedForTranslatableAttachments()
    {
        $model = new TestModelTranslatableAttachment;

        $this->assertInstanceOf(TranslatableAttachOne::class, $model->image());
        $this->assertInstanceOf(TranslatableAttachMany::class, $model->gallery());
        $this->assertSame(AttachOne::class, get_class($model->cover()));
        $this->assertSame(['image', 'gallery', 'documents'], $model->getTranslatableAttachments());
    }

    /**
     * testExtendedRelationsAreSwapped confirms relations added by extension callbacks are swapped
     */
    public function testExtendedRelationsAreSwapped()
    {
        TestModelTranslatableAttachmentExtended::extend(function ($model) {
            $model->attachOne['banner'] = File::class;
            $model->translatable[] = 'banner';
        });

        $model = new TestModelTranslatableAttachmentExtended;

        $this->assertInstanceOf(TranslatableAttachOne::class, $model->banner());
    }

    /**
     * testDefaultLocaleStoresUnderRelationName confirms the default locale keeps the plain field value
     */
    public function testDefaultLocaleStoresUnderRelationName()
    {
        $model = $this->makeModel();
        $model->image()->add($this->makeFile('base.jpg'));

        $this->assertSame('image', File::first()->field);
        $this->assertSame('image', $model->image()->getAttachmentField());
        $this->assertSame('base.jpg', TestModelTranslatableAttachment::find($model->id)->image->file_name);
    }

    /**
     * testLocaleStoresUnderSuffixedField confirms a non-default locale suffixes the field and keeps the default file
     */
    public function testLocaleStoresUnderSuffixedField()
    {
        $model = $this->makeModel();
        $model->image()->add($this->makeFile('base.jpg'));

        $french = $this->findInLocale($model, 'fr');
        $french->image()->add($this->makeFile('french.jpg'));

        $this->assertSame('image:fr', File::where('file_name', 'french.jpg')->value('field'));
        $this->assertSame('image', File::where('file_name', 'base.jpg')->value('field'));
        $this->assertSame(TestModelTranslatableAttachment::class, File::where('file_name', 'french.jpg')->value('attachment_type'));
    }

    /**
     * testAttachOneFallsBackToDefault confirms property reads fall back while relation queries stay strict
     */
    public function testAttachOneFallsBackToDefault()
    {
        $model = $this->makeModel();
        $model->image()->add($this->makeFile('base.jpg'));

        $french = $this->findInLocale($model, 'fr');
        $this->assertSame('base.jpg', $french->image->file_name);
        $this->assertNull($french->image()->first());
        $this->assertNull($french->image()->withoutFallback()->getResults());

        $french->image()->add($this->makeFile('french.jpg'));
        $this->assertSame('french.jpg', $this->findInLocale($model, 'fr')->image->file_name);
        $this->assertSame('base.jpg', $this->findInLocale($model, 'en')->image->file_name);
    }

    /**
     * testAssigningFileStoresUnderActiveLocale confirms property assignment writes to the active locale on save
     */
    public function testAssigningFileStoresUnderActiveLocale()
    {
        $model = $this->makeModel();
        $model->image()->add($this->makeFile('base.jpg'));

        $french = $this->findInLocale($model, 'fr');
        $file = $this->makeFile('french.jpg');
        $file->save();
        $french->image = $file;
        $french->save();

        $this->assertSame('image:fr', File::where('file_name', 'french.jpg')->value('field'));
        $this->assertSame('image', File::where('file_name', 'base.jpg')->value('field'));
        $this->assertSame('french.jpg', $this->findInLocale($model, 'fr')->image->file_name);
    }

    /**
     * testAttachManyLocaleReplacesWholeSet confirms locale files replace the default set rather than merge with it
     */
    public function testAttachManyLocaleReplacesWholeSet()
    {
        $model = $this->makeModel();
        foreach (['one.jpg', 'two.jpg', 'three.jpg'] as $name) {
            $model->gallery()->add($this->makeFile($name));
        }

        $french = $this->findInLocale($model, 'fr');
        $this->assertCount(3, $french->gallery);

        $french->gallery()->add($this->makeFile('french.jpg'));
        $gallery = $this->findInLocale($model, 'fr')->gallery;
        $this->assertCount(1, $gallery);
        $this->assertSame('french.jpg', $gallery->first()->file_name);
    }

    /**
     * testAddingLocaleFileKeepsDefaultFile confirms singular cleanup only touches the active locale
     */
    public function testAddingLocaleFileKeepsDefaultFile()
    {
        $model = $this->makeModel();
        $model->image()->add($this->makeFile('base.jpg'));

        $french = $this->findInLocale($model, 'fr');
        $french->image()->add($this->makeFile('first.jpg'));
        $french->image()->add($this->makeFile('second.jpg'));

        $this->assertSame(['base.jpg', 'second.jpg'], File::orderBy('id')->pluck('file_name')->all());
    }

    /**
     * testEagerLoadResolvesFallbackPerParent confirms each parent gets its own locale files or the default files
     */
    public function testEagerLoadResolvesFallbackPerParent()
    {
        $translated = $this->makeModel('Translated');
        $translated->gallery()->add($this->makeFile('base-a.jpg'));
        $translated->gallery()->add($this->makeFile('base-b.jpg'));
        $this->findInLocale($translated, 'fr')->gallery()->add($this->makeFile('french-a.jpg'));

        $untranslated = $this->makeModel('Untranslated');
        $untranslated->gallery()->add($this->makeFile('base-c.jpg'));

        TestModelTranslatableAttachment::$activeLocale = 'fr';
        $models = TestModelTranslatableAttachment::with('gallery', 'image')->orderBy('id')->get();

        $this->assertSame(['french-a.jpg'], $models[0]->gallery->pluck('file_name')->all());
        $this->assertSame(['base-c.jpg'], $models[1]->gallery->pluck('file_name')->all());
    }

    /**
     * testExistenceQueriesResolveFallback confirms has and withCount see the resolved files
     */
    public function testExistenceQueriesResolveFallback()
    {
        $translated = $this->makeModel('Translated');
        foreach (['one.jpg', 'two.jpg', 'three.jpg'] as $name) {
            $translated->gallery()->add($this->makeFile($name));
        }
        $this->findInLocale($translated, 'fr')->gallery()->add($this->makeFile('french.jpg'));

        $untranslated = $this->makeModel('Untranslated');
        $untranslated->gallery()->add($this->makeFile('base.jpg'));

        $this->makeModel('Empty');

        TestModelTranslatableAttachment::$activeLocale = 'fr';
        $counts = TestModelTranslatableAttachment::withCount('gallery')->orderBy('id')->pluck('gallery_count')->all();

        $this->assertEquals([1, 1, 0], $counts);
        $this->assertSame(2, TestModelTranslatableAttachment::has('gallery')->count());
    }

    /**
     * testSetLocaleSwitchesLoadedAttachments confirms a loaded attachment is reloaded for the new locale
     */
    public function testSetLocaleSwitchesLoadedAttachments()
    {
        $model = $this->makeModel();
        $model->image()->add($this->makeFile('base.jpg'));
        $this->findInLocale($model, 'fr')->image()->add($this->makeFile('french.jpg'));

        $fresh = $this->findInLocale($model, 'en');
        $this->assertSame('base.jpg', $fresh->image->file_name);

        $fresh->setLocale('fr');
        $this->assertSame('french.jpg', $fresh->image->file_name);
    }

    /**
     * testRemovingLocaleFileFallsBackToDefault confirms removal does not cache an empty value
     */
    public function testRemovingLocaleFileFallsBackToDefault()
    {
        $model = $this->makeModel();
        $model->image()->add($this->makeFile('base.jpg'));

        $french = $this->findInLocale($model, 'fr');
        $french->image()->add($this->makeFile('french.jpg'));
        $this->assertSame('french.jpg', $french->image->file_name);

        $french->image()->remove($french->image);
        $this->assertSame('base.jpg', $french->image->file_name);
        $this->assertSame(['base.jpg'], File::pluck('file_name')->all());
    }

    /**
     * testDefaultFileCannotBeRemovedFromLocale confirms a locale relation leaves the inherited default file alone
     */
    public function testDefaultFileCannotBeRemovedFromLocale()
    {
        $model = $this->makeModel();
        $model->image()->add($this->makeFile('base.jpg'));

        $french = $this->findInLocale($model, 'fr');
        $french->image()->remove($french->image);

        $this->assertSame('image', File::where('file_name', 'base.jpg')->value('field'));
    }

    /**
     * testDeferredBindingCommitsToActiveLocale confirms a deferred upload lands under the locale it was saved in
     */
    public function testDeferredBindingCommitsToActiveLocale()
    {
        $model = $this->makeModel();
        $model->gallery()->add($this->makeFile('base.jpg'));

        $french = $this->findInLocale($model, 'fr');
        $file = $this->makeFile('french.jpg');
        $file->save();
        $french->gallery()->add($file, 'session-key');

        $this->assertCount(0, $french->gallery()->withoutFallback()->getResults());

        $french->name = 'Produit';
        $french->save(null, 'session-key');

        $this->assertSame('gallery:fr', File::where('file_name', 'french.jpg')->value('field'));
        $this->assertSame(['french.jpg'], $this->findInLocale($model, 'fr')->gallery->pluck('file_name')->all());
    }

    /**
     * testAttachmentTranslationApi confirms the trait API reports and forgets attachment translations
     */
    public function testAttachmentTranslationApi()
    {
        $model = $this->makeModel();
        $model->image()->add($this->makeFile('base.jpg'));
        $this->findInLocale($model, 'fr')->image()->add($this->makeFile('french.jpg'));
        $this->findInLocale($model, 'de')->image()->add($this->makeFile('german.jpg'));
        $this->findInLocale($model, 'es')->image()->add($this->makeFile('spanish.jpg'));

        $fresh = $this->findInLocale($model, 'fr');
        $this->assertTrue($fresh->hasAttachmentTranslation('image'));
        $this->assertTrue($fresh->hasAttachmentTranslation('image', 'en'));
        $this->assertFalse($fresh->hasAttachmentTranslation('image', 'it'));
        $this->assertFalse($fresh->hasAttachmentTranslation('gallery', 'fr'));
        $this->assertFalse($fresh->hasAttachmentTranslation('name', 'fr'));
        $this->assertSame(['fr', 'de', 'es'], $fresh->getTranslatedAttachmentLocales('image'));

        $fresh->forgetAttachmentTranslation('image', 'fr');
        $this->assertSame(['base.jpg', 'german.jpg', 'spanish.jpg'], File::orderBy('id')->pluck('file_name')->all());
        $this->assertSame('base.jpg', $fresh->image->file_name);

        $fresh->forgetAttachmentTranslations('image');
        $this->assertSame(['base.jpg'], File::pluck('file_name')->all());
    }

    /**
     * testRecordLevelMethodsIgnoreAttachments confirms the attribute trait API only reports attribute translations
     */
    public function testRecordLevelMethodsIgnoreAttachments()
    {
        $model = $this->makeModel();
        $this->findInLocale($model, 'fr')->image()->add($this->makeFile('french.jpg'));

        $fresh = $this->findInLocale($model, 'en');
        $this->assertSame([], $fresh->getTranslatedLocales());
        $this->assertFalse($fresh->hasTranslations('fr'));

        $fresh->forgetAllTranslations('fr');
        $this->assertSame(['french.jpg'], File::pluck('file_name')->all());
    }

    /**
     * testTranslatableAloneSharesAttachments confirms attachments stay shared without the attachments trait
     */
    public function testTranslatableAloneSharesAttachments()
    {
        $model = new TestModelTranslatableSharedAttachment;

        $this->assertSame(AttachOne::class, get_class($model->image()));
    }

    /**
     * testAttachmentsTraitRequiresTranslatable confirms a clear error when the Translatable trait is missing
     */
    public function testAttachmentsTraitRequiresTranslatable()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('requires the Translatable trait');

        new TestModelTranslatableAttachmentsOnly;
    }

    /**
     * testDeletingModelRemovesEveryLocale confirms every locale's files are deleted or orphaned with the record
     */
    public function testDeletingModelRemovesEveryLocale()
    {
        $model = $this->makeModel();
        $model->image()->add($this->makeFile('base.jpg'));
        $model->documents()->add($this->makeFile('base.pdf'));

        $french = $this->findInLocale($model, 'fr');
        $french->image()->add($this->makeFile('french.jpg'));
        $french->documents()->add($this->makeFile('french.pdf'));

        $this->findInLocale($model, 'fr')->delete();

        $this->assertSame(['base.pdf', 'french.pdf'], File::orderBy('id')->pluck('file_name')->all());
        $this->assertSame(0, File::whereNotNull('attachment_id')->count());
    }

    /**
     * makeModel creates a saved model in the default locale
     */
    protected function makeModel($name = 'Product')
    {
        TestModelTranslatableAttachment::$activeLocale = 'en';

        return TestModelTranslatableAttachment::create(['name' => $name]);
    }

    /**
     * findInLocale loads a fresh copy of the model with the given active locale
     */
    protected function findInLocale($model, $locale)
    {
        TestModelTranslatableAttachment::$activeLocale = $locale;

        return TestModelTranslatableAttachment::find($model->id);
    }

    /**
     * makeFile returns an unsaved file model with a file name
     */
    protected function makeFile($name)
    {
        $file = new File;
        $file->file_name = $name;

        return $file;
    }
}

class TestModelTranslatableAttachment extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\Translatable;
    use \October\Rain\Database\Traits\TranslatableAttachments;

    public static $activeLocale = 'en';

    public $translatable = ['name', 'image', 'gallery', 'documents'];

    protected $fillable = ['name'];

    protected $table = 'test_translatable_attachments';

    public $attachOne = [
        'image' => [File::class, 'delete' => true],
        'cover' => File::class
    ];

    public $attachMany = [
        'gallery' => [File::class, 'delete' => true],
        'documents' => [File::class, 'delete' => false]
    ];

    protected function resolveTranslatableLocale()
    {
        return static::$activeLocale;
    }

    protected function resolveTranslatableDefaultLocale()
    {
        return 'en';
    }
}

class TestModelTranslatableAttachmentExtended extends TestModelTranslatableAttachment
{
}

class TestModelTranslatableSharedAttachment extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\Translatable;

    public $translatable = ['name', 'image'];

    protected $table = 'test_translatable_attachments';

    public $attachOne = [
        'image' => File::class
    ];

    protected function resolveTranslatableLocale()
    {
        return 'fr';
    }

    protected function resolveTranslatableDefaultLocale()
    {
        return 'en';
    }
}

class TestModelTranslatableAttachmentsOnly extends \October\Rain\Database\Model
{
    use \October\Rain\Database\Traits\TranslatableAttachments;

    public $translatable = ['image'];

    protected $table = 'test_translatable_attachments';
}
