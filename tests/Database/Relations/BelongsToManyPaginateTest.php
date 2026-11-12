<?php

use Illuminate\Database\Capsule\Manager;
use Illuminate\Pagination\Paginator;
use October\Rain\Database\Model;

/**
 * BelongsToManyPaginateTest
 */
class BelongsToManyPaginateTest extends TestCase
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

        $capsule->schema()->create('test_paginate_posts', function ($table) {
            $table->increments('id');
        });

        $capsule->schema()->create('test_paginate_tags', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
        });

        $capsule->schema()->create('test_paginate_post_tag', function ($table) {
            $table->integer('post_id');
            $table->integer('tag_id');
        });

        Paginator::currentPageResolver(function ($pageName = 'page') {
            return $pageName === 'tagsPage' ? 2 : 1;
        });
    }

    /**
     * tearDown test
     */
    public function tearDown(): void
    {
        Paginator::currentPageResolver(function () {
            return 1;
        });
    }

    /**
     * testPaginateUsesThePageName confirms the page is read from the given page name
     */
    public function testPaginateUsesThePageName()
    {
        $post = $this->makePostWithTags();

        $paginator = $post->tags()->paginate(1, ['*'], 'tagsPage');

        $this->assertSame(2, $paginator->currentPage());
        $this->assertSame('tagsPage', $paginator->getPageName());
        $this->assertSame('Second', $paginator->first()->name);
    }

    /**
     * testSimplePaginateUsesThePageName confirms the page is read from the given page name
     */
    public function testSimplePaginateUsesThePageName()
    {
        $post = $this->makePostWithTags();

        $paginator = $post->tags()->simplePaginate(1, ['*'], 'tagsPage');

        $this->assertSame(2, $paginator->currentPage());
        $this->assertSame('Second', $paginator->first()->name);
    }

    /**
     * testLegacySignatureStillSetsThePage confirms paginate($perPage, $currentPage) keeps working
     */
    public function testLegacySignatureStillSetsThePage()
    {
        $post = $this->makePostWithTags();

        $paginator = $post->tags()->paginate(1, 2);

        $this->assertSame(2, $paginator->currentPage());
        $this->assertSame('Second', $paginator->first()->name);
    }

    /**
     * makePostWithTags creates a post with two tags
     */
    protected function makePostWithTags(): TestPaginatePost
    {
        $post = TestPaginatePost::create([]);

        foreach (['First', 'Second'] as $name) {
            $post->tags()->attach(TestPaginateTag::create(['name' => $name])->id);
        }

        return $post;
    }
}

/**
 * TestPaginatePost example class
 */
class TestPaginatePost extends Model
{
    protected $table = 'test_paginate_posts';

    public $timestamps = false;

    public $belongsToMany = [
        'tags' => [
            TestPaginateTag::class,
            'table' => 'test_paginate_post_tag',
            'key' => 'post_id',
            'otherKey' => 'tag_id',
            'order' => 'test_paginate_tags.id',
        ],
    ];
}

/**
 * TestPaginateTag example class
 */
class TestPaginateTag extends Model
{
    protected $table = 'test_paginate_tags';

    protected $fillable = ['name'];

    public $timestamps = false;
}
