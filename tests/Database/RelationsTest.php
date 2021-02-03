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
            $table->string('type')->index();
            $table->string('name');
            $table->timestamps();
        });

        $this->db->schema()->create('posts_terms', function ($table) {
            $table->primary(['post_id', 'term_id']);
            $table->unsignedInteger('post_id');
            $table->unsignedInteger('term_id');
            $table->string('data')->nullable();
            $table->timestamps();
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
        $this->assertEquals(2, $post->tags()->count());
        $this->assertEquals(2, $post->categories()->count());
        $this->assertEquals(4, $post->terms()->count());
    }

    public function testBelongsToManySyncAll()
    {
        $post = Post::first();

        $catid = $post->categories()->first()->id;
        $tagid = $post->tags()->first()->id;

        $this->assertEquals(2, $post->categories()->count());
        $this->assertEquals(2, $post->tags()->count());

        $post->categories()->sync([$catid]);
        $post->tags()->sync([$tagid]);

        $post->reloadRelations();

        $this->assertEquals(1, $post->categories()->count());
        $this->assertEquals($catid, $post->categories()->first()->id);

        $this->assertEquals(1, $post->tags()->count());
        $this->assertEquals($tagid, $post->tags()->first()->id);

        $this->assertEquals(2, $post->terms()->count());
    }

    public function testBelongsToManySyncTags()
    {
        $post = Post::first();

        $id = $post->tags()->first()->id;

        $post->categories()->detach();
        $post->tags()->sync([$id]);

        $this->assertEquals(0, $post->categories()->count());
        $this->assertEquals(1, $post->tags()->count());
        $this->assertEquals($id, $post->tags()->first()->id);

        $this->assertEquals(1, $post->terms()->count());
    }

    public function testBelongsToManySyncCategories()
    {
        $post = Post::first();

        $id = $post->categories()->first()->id;

        $post->categories()->sync([$id]);
        $post->tags()->detach();

        $this->assertEquals(1, $post->categories()->count());
        $this->assertEquals($id, $post->categories()->first()->id);
        $this->assertEquals(0, $post->tags()->count());

        $this->assertEquals(1, $post->terms()->count());
    }

    public function testBelongsToManyDetach()
    {
        $post = Post::first();

        $post->categories()->detach();
        $post->tags()->detach();

        $this->assertEquals(0, $post->categories()->count());
        $this->assertEquals(0, $post->tags()->count());
        $this->assertEquals(0, $post->terms()->count());
    }

    public function testBelongsToManySyncMultipleCategories()
    {
        $post = Post::first();

        $category_ids = Term::where('type', 'category')->lists('id');
        $this->assertEquals(4, count($category_ids));

        $post->categories()->sync($category_ids);
        $this->assertEquals(4, $post->categories()->count());
        $this->assertEquals(2, $post->tags()->count());
        $this->assertEquals(6, $post->terms()->count());
    }

    public function testBelongsToManyDetachOneCategory()
    {
        $post = Post::first();

        $id = $post->categories()->get()->last()->id;

        $this->assertEquals(2, $post->categories()->count());
        $this->assertEquals(2, $post->tags()->count());
        $this->assertEquals(4, $post->terms()->count());

        $post->categories()->detach([$id]);
        $post->reloadRelations();

        $this->assertEquals(1, $post->categories()->count());
        $this->assertEquals(2, $post->tags()->count());
        $this->assertEquals(3, $post->terms()->count());
    }

    public function testPivotData()
    {
        $data = 'My Pivot Data';
        $post = Post::first();

        $id = $post->categories()->get()->last()->id;
        $updated = $post->categories()->updateExistingPivot($id, [ 'data' => $data ]);
        $this->assertTrue($updated === 1);

        $category = $post->categories()->find($id);
        $this->assertEquals($data, $category->pivot->data);
    }

    public function testTerms()
    {
        $post = Post::create([
            'title' => 'B Post',
        ]);

        $term1 = Term::create(['name' => 'term #1', 'type' => 'any']);
        $term2 = Term::create(['name' => 'term #2', 'type' => 'any']);
        $term3 = Term::create(['name' => 'term #3', 'type' => 'any']);

        // Add/remove to collection
        $this->assertFalse($post->terms->contains($term1->id));
        $post->terms()->add($term1);
        $post->terms()->add($term2);
        $this->assertTrue($post->terms->contains($term1->id));
        $this->assertTrue($post->terms->contains($term2->id));

        // Set by Model object
        $post->terms = $term1;
        $this->assertEquals(1, $post->terms->count());
        $this->assertEquals('term #1', $post->terms->first()->name);

        $post->terms = [$term1, $term2, $term3];
        $this->assertEquals(3, $post->terms->count());

        // Set by primary key
        $post->terms = $term2->id;
        $this->assertEquals(1, $post->terms->count());
        $this->assertEquals('term #2', $post->terms->first()->name);

        $post->terms = [$term2->id, $term3->id];
        $this->assertEquals(2, $post->terms->count());

        // Nullify
        $post->terms = null;
        $this->assertEquals(0, $post->terms->count());

        // Extra nullify checks (still exists in DB until saved)
        $post->reloadRelations('terms');
        $this->assertEquals(2, $post->terms->count());
        $post->save();
        $post->reloadRelations('terms');
        $this->assertEquals(0, $post->terms->count());

        // Deferred in memory
        $post->terms = [$term2->id, $term3->id];
        $this->assertEquals(2, $post->terms->count());
        $this->assertEquals('term #2', $post->terms->first()->name);
    }

    public function testUndefinedMorphsRelation()
    {
        $this->expectException('BadMethodCallException');

        $morphs = new Morphs;
        $morphs->unknownRelation();
    }

    public function testDefinedMorphsRelation()
    {
        $morphs = new Morphs;
        $value = $morphs->related();
    }
}

class Post extends \October\Rain\Database\Model
{
    public $table = 'posts';

    public $fillable = ['title'];

    protected $dates = [
        'created_at',
        'updated_at',
        'episode_at'
    ];

    public $belongsToMany = [
        'tags' => [
            Term::class,
            'table'     => 'posts_terms',
            'key'       => 'post_id',
            'otherKey'  => 'term_id',
            'pivot'     => ['data'],
            'timestamps' => true,
            'conditions' => 'type = "tag"',
        ],
        'categories' => [
            Term::class,
            'table'     => 'posts_terms',
            'key'       => 'post_id',
            'otherKey'  => 'term_id',
            'pivot'     => ['data'],
            'timestamps' => true,
            'conditions' => 'type = "category"',
        ],
        'terms' => [
            Term::class,
            'table'     => 'posts_terms',
            'key'       => 'post_id',
            'otherKey'  => 'term_id',
            'timestamps' => true,
        ],
    ];
}

class Term extends \October\Rain\Database\Model
{
    public $table = 'terms';

    public $fillable = ['type', 'name'];

    protected $dates = [
        'created_at',
        'updated_at',
        'episode_at'
    ];

    public $belongsToMany = [
        'posts' => [
            'Post',
            'table'      => 'posts_terms',
            'key'        => 'term_id',
            'otherKey'   => 'post_id',
            'pivot'     => ['data'],
            'timestamps' => true,
            'conditions' => 'type = "post"',
        ],
    ];
}

class Morphs extends \October\Rain\Database\Model
{
    public $table = 'morphs';

    public $morphTo = [
        'related' => [],
    ];
}
