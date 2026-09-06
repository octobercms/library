<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use October\Rain\Database\Attach\File;
use October\Rain\Database\Model;

class AttachmentEagerLoadingTest extends TestCase
{
    protected $connection;

    protected $savedResolver;

    protected $savedDispatcher;

    public function setUp(): void
    {
        parent::setUp();

        $this->savedResolver = EloquentModel::getConnectionResolver();
        $this->savedDispatcher = EloquentModel::getEventDispatcher();

        $capsule = new Capsule;
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $capsule->bootEloquent();
        EloquentModel::unsetEventDispatcher();
        $this->connection = $capsule->getConnection();

        $schema = $this->connection->getSchemaBuilder();
        $schema->create('attachment_owners', function ($table) {
            $table->increments('id');
        });
        $schema->create('attachment_files', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('attachment_id');
            $table->string('attachment_type');
            $table->string('field');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
        });
        $this->connection->enableQueryLog();
        AttachmentEagerLoadingFile::$hydrated = [];
        AttachmentEagerLoadingOwner::$relationsCreated = [];
    }

    public function tearDown(): void
    {
        if ($this->savedResolver) {
            EloquentModel::setConnectionResolver($this->savedResolver);
        }
        else {
            EloquentModel::unsetConnectionResolver();
        }

        if ($this->savedDispatcher) {
            EloquentModel::setEventDispatcher($this->savedDispatcher);
        }
        else {
            EloquentModel::unsetEventDispatcher();
        }

        parent::tearDown();
    }

    public function testCoverDoesNotHydrateUnrequestedGallery()
    {
        $this->seedOwner(1, 100);
        $this->connection->flushQueryLog();

        $owner = AttachmentEagerLoadingOwner::with('cover')->first();

        $this->assertSame('cover', $owner->cover->field);
        $this->assertFalse($owner->relationLoaded('gallery'));
        $this->assertCount(1, $this->attachmentQueries());
        $this->assertSame(['cover'], AttachmentEagerLoadingFile::$hydrated);
    }

    public function testCompatibleFieldsShareOneQueryAndMatchMultipleOwners()
    {
        $this->seedOwner(1, 2);
        $this->seedOwner(2, 3);
        $this->connection->table('attachment_owners')->insert(['id' => 3]);
        $this->connection->flushQueryLog();

        $owners = AttachmentEagerLoadingOwner::with(['cover', 'gallery'])->orderBy('id')->get();

        $this->assertCount(1, $this->attachmentQueries());
        $this->assertCount(7, AttachmentEagerLoadingFile::$hydrated);
        foreach ([0 => 2, 1 => 3] as $index => $count) {
            $this->assertSame($owners[$index]->id, $owners[$index]->cover->attachment_id);
            $this->assertCount($count, $owners[$index]->gallery);
            $this->assertSame([$owners[$index]->id], $owners[$index]->gallery->pluck('attachment_id')->unique()->values()->all());
        }
        $this->assertNull($owners[2]->cover);
        $this->assertCount(0, $owners[2]->gallery);
    }

    public function testDifferentFileClassesUseSeparateQueriesAndFieldSets()
    {
        $this->seedOwner(1, 2);
        $this->seedFile(1, 'document');
        $this->connection->flushQueryLog();

        $owner = AttachmentEagerLoadingOwner::with(['cover', 'document'])->first();

        $this->assertInstanceOf(AttachmentEagerLoadingFile::class, $owner->cover);
        $this->assertInstanceOf(AttachmentEagerLoadingDocument::class, $owner->document);
        $this->assertCount(2, $this->attachmentQueries());
        $this->assertSame(['cover', 'document'], AttachmentEagerLoadingFile::$hydrated);
    }

    public function testGroupingConstructsEachRequestedRelationOnlyOnce()
    {
        $this->seedOwner(1, 2);
        $this->seedFile(1, 'document');

        AttachmentEagerLoadingOwner::with(['cover', 'gallery', 'document'])->first();

        $this->assertSame(['cover', 'gallery', 'document'], AttachmentEagerLoadingOwner::$relationsCreated);
    }

    public function testScopedAndOptedOutFieldsUseTheirOwnQueries()
    {
        $this->seedOwner(1, 2);
        foreach (['scoped', 'conditional', 'separate'] as $field) {
            $this->seedFile(1, $field, true);
            $this->seedFile(1, $field, false);
        }
        $this->connection->flushQueryLog();

        $owner = AttachmentEagerLoadingOwner::with(['cover', 'scoped', 'conditional', 'separate'])->first();

        $this->assertCount(4, $this->attachmentQueries());
        $this->assertCount(1, $owner->scoped);
        $this->assertCount(1, $owner->conditional);
        $this->assertCount(2, $owner->separate);
        $this->assertSame(['cover', 'scoped', 'conditional', 'separate', 'separate'], AttachmentEagerLoadingFile::$hydrated);
    }

    public function testNestedEagerLoadsStillLoadTheRelatedOwner()
    {
        $this->seedOwner(1, 100);
        $this->connection->flushQueryLog();

        $owner = AttachmentEagerLoadingOwner::with('cover.attachment')->first();

        $this->assertTrue($owner->cover->relationLoaded('attachment'));
        $this->assertSame($owner->id, $owner->cover->attachment->id);
        $this->assertCount(1, $this->attachmentQueries());
        $this->assertSame(['cover'], AttachmentEagerLoadingFile::$hydrated);
    }

    public function testNoAttachmentsAreQueriedWithoutEagerLoads()
    {
        $this->seedOwner(1, 2);
        $this->connection->flushQueryLog();

        $owner = AttachmentEagerLoadingOwner::first();

        $this->assertFalse($owner->relationLoaded('cover'));
        $this->assertCount(0, $this->attachmentQueries());
        $this->assertSame([], AttachmentEagerLoadingFile::$hydrated);
    }

    public function testReusedBuilderCanRequestAnotherAttachmentField()
    {
        $this->seedOwner(1, 2);
        $query = AttachmentEagerLoadingOwner::with('cover');
        $query->get();
        AttachmentEagerLoadingFile::$hydrated = [];
        $this->connection->flushQueryLog();

        $owner = $query->with('gallery')->first();

        $this->assertCount(2, $owner->gallery);
        $this->assertCount(1, $this->attachmentQueries());
        $this->assertSame(['cover', 'gallery', 'gallery'], AttachmentEagerLoadingFile::$hydrated);
    }

    public function testReusedBuilderRefreshesAttachmentsForNewOwners()
    {
        $this->seedOwner(1, 1);
        $this->seedOwner(2, 2);
        $query = AttachmentEagerLoadingOwner::with(['cover', 'gallery'])->orderBy('id')->limit(1);
        $query->get();
        AttachmentEagerLoadingFile::$hydrated = [];
        $this->connection->flushQueryLog();

        $owner = $query->offset(1)->first();

        $this->assertSame(2, $owner->id);
        $this->assertNotNull($owner->cover);
        $this->assertSame(2, $owner->cover->attachment_id);
        $this->assertCount(2, $owner->gallery);
        $this->assertCount(1, $this->attachmentQueries());
        $this->assertCount(3, AttachmentEagerLoadingFile::$hydrated);
    }

    public function testCombinedQueryPreservesTheFirstCustomConstraint()
    {
        $this->seedOwner(1, 1);
        $this->seedFile(1, 'gallery', false);
        $this->connection->flushQueryLog();

        $owner = AttachmentEagerLoadingOwner::with([
            'cover' => function ($relation) {
                $relation->where('is_public', true);
            },
            'gallery' => function ($relation) {
                $relation->where('is_public', false);
            },
        ])->first();

        $this->assertNotNull($owner->cover);
        $this->assertCount(1, $owner->gallery);
        $this->assertSame(1, $owner->gallery->first()->is_public);
        $this->assertCount(1, $this->attachmentQueries());
    }

    protected function seedOwner(int $id, int $galleryCount): void
    {
        $this->connection->table('attachment_owners')->insert(['id' => $id]);
        $this->seedFile($id, 'cover');
        for ($index = 0; $index < $galleryCount; $index++) {
            $this->seedFile($id, 'gallery');
        }
    }

    protected function seedFile(int $ownerId, string $field, bool $isPublic = true): void
    {
        $this->connection->table('attachment_files')->insert([
            'attachment_id' => $ownerId,
            'attachment_type' => AttachmentEagerLoadingOwner::class,
            'field' => $field,
            'is_public' => $isPublic,
        ]);
    }

    protected function attachmentQueries(): array
    {
        return array_values(array_filter($this->connection->getQueryLog(), function ($query) {
            return strpos($query['query'], 'from "attachment_files"') !== false;
        }));
    }
}

class AttachmentEagerLoadingOwner extends Model
{
    protected $table = 'attachment_owners';

    public $timestamps = false;

    public static $relationsCreated = [];

    public $attachOne = [
        'cover' => AttachmentEagerLoadingFile::class,
        'document' => AttachmentEagerLoadingDocument::class,
    ];

    public $attachMany = [
        'gallery' => AttachmentEagerLoadingFile::class,
        'scoped' => [AttachmentEagerLoadingFile::class, 'scope' => 'visible'],
        'conditional' => [AttachmentEagerLoadingFile::class, 'conditions' => 'is_public = 1'],
        'separate' => [AttachmentEagerLoadingFile::class, 'combineEager' => false],
    ];

    protected function beforeRelation($name, $relation)
    {
        static::$relationsCreated[] = $name;
    }
}

class AttachmentEagerLoadingFile extends File
{
    protected $table = 'attachment_files';

    public $timestamps = false;

    public static $hydrated = [];

    public function newFromBuilder($attributes = [], $connection = null)
    {
        $model = parent::newFromBuilder($attributes, $connection);
        static::$hydrated[] = $model->field;

        return $model;
    }

    public function scopeVisible($query)
    {
        return $query->where('is_public', true);
    }
}

class AttachmentEagerLoadingDocument extends AttachmentEagerLoadingFile
{
}
