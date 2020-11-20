<?php

class RelationsTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->createTables();
        $this->seedTables();
    }

    public function createTables()
    {
        $this->db->schema()->create('posts', function ($table) {
            $table->increments('id');
            $table->string('title')->default('');
            $table->timestamps();
        });

        $this->db->schema()->create('terms', function ($table) {
            $table->increments('id');
            $table->string('type');
            $table->string('name');
            $table->timestamps();
        });

        $this->db->schema()->create('posts_terms', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
            $table->unsignedInteger('term_id');
        });
    }

    public function seedTables()
    {
        $post = Post::create([
            'title' => 'A Post',
        ]);

        Term::create(['type' => 'category', 'name' => 'category tag #1']);
        Term::create(['type' => 'category', 'name' => 'category tag #2']);

        $post->tags()->create(['type' => 'tag', 'name' => 'A Tag']);
        $post->tags()->create(['type' => 'tag', 'name' => 'Second Tag']);

        $post->categories()->create(['type' => 'category', 'name' => 'A Category']);
        $post->categories()->create(['type' => 'category', 'name' => 'Second Category']);
    }

    public function testTablesExist()
    {
        $this->assertTrue($this->db->schema()->hasTable('posts'));
        $this->assertTrue($this->db->schema()->hasTable('terms'));
        $this->assertTrue($this->db->schema()->hasTable('posts_terms'));
    }

    public function testTablesProperlySeeded()
    {
        $this->assertEquals(1, Post::count());
        $this->assertEquals(6, Term::count());
        $this->assertEquals(2, Term::where('type', 'tag')->count());
        $this->assertEquals(4, Term::where('type', 'category')->count());
    }

    public function testBelongsToManyCount()
    {
        $post = Post::first();
        $this->assertEquals(2, $post->tags->count());
        $this->assertEquals(2, $post->categories->count());
    }

    public function testBelongsToManySyncAll()
    {
        $post = Post::first();

        $catid = $post->categories()->first()->id;
        $tagid = $post->tags()->first()->id;

        Post::flushDuplicateCache();

        $post->categories()->sync([$catid]);

        $this->assertEquals(1, $post->categories->count());
        $this->assertEquals($catid, $post->categories()->first()->id);

        $post->tags()->sync([$tagid]);

        $this->assertEquals(1, $post->tags->count());
        $this->assertEquals($tagid, $post->tags()->first()->id);
    }

    public function testBelongsToManySyncTags()
    {
        $post = Post::first();

        $id = $post->tags()->first()->id;

        Post::flushDuplicateCache();

        $post->categories()->detach();
        $this->assertEquals(0, $post->categories()->count());

        $post->tags()->sync([$id]);

        $this->assertEquals(1, $post->tags->count());
        $this->assertEquals($id, $post->tags()->first()->id);
    }

    public function testBelongsToManySyncCategories()
    {
        $post = Post::first();

        $id = $post->categories()->first()->id;

        Post::flushDuplicateCache();

        $post->categories()->sync([$id]);

        $this->assertEquals(1, $post->categories()->count());
        $this->assertEquals($id, $post->categories()->first()->id);

        $post->tags()->detach();
        $this->assertEquals(0, $post->tags->count());
    }

    public function testBelongsToManyDetach()
    {
        $post = Post::first();

        $post->categories()->detach();
        $this->assertEquals(0, $post->categories()->count());

        $post->tags()->detach();
        $this->assertEquals(0, $post->tags()->count());
    }

    public function testBelongsToManySyncMultipleCategories()
    {
        $post = Post::first();

        $category_ids = Term::where('type', 'category')->lists('id');
        $this->assertEquals(4, count($category_ids));

        $post->categories()->sync($category_ids);
        $this->assertEquals(4, $post->categories()->count());
    }

    public function testBelongsToManyDetachOneCategory()
    {
        $post = Post::first();

        $id = $post->categories()->get()->last()->id;
        Post::flushDuplicateCache();

        $post->categories()->detach([$id]);
        $this->assertEquals(1, $post->categories()->count());
    }
}

class Post extends \October\Rain\Database\Model
{
    public $table = 'posts';

    public $fillable = ['title'];

    public $belongsToMany = [
        'tags' => [
            Term::class,
            'table'     => 'posts_terms',
            'key'       => 'post_id',
            'otherKey'  => 'term_id',
            'conditions' => 'type = "tag"'
        ],
        'categories' => [
            Term::class,
            'table'     => 'posts_terms',
            'key'       => 'post_id',
            'otherKey'  => 'term_id',
            'conditions' => 'type = "category"'
        ],
    ];
}

class Term extends \October\Rain\Database\Model
{
    public $table = 'terms';

    public $fillable = ['type', 'name'];

    public $belongsToMany = [
        'posts' => [
            'Post',
            'table'      => 'posts_terms',
            'key'        => 'term_id',
            'otherKey'   => 'post_id',
            'conditions' => 'type = "post"'
        ],
    ];
}
