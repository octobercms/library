<?php

use Carbon\Carbon;

class RelationsTest extends DbTestCase
{
    protected $seeded = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->seeded = [
            'posts' => [],
            'categories' => [],
            'labels' => [],
            'tags' => []
        ];

        $this->createTables();
    }

    public function testBelongsToManyCount()
    {
        $post = $this->seeded['posts'][0];
        $this->assertEquals(1, $post->categories()->count());
        $this->assertEquals(2, $post->tags()->count());
        $this->assertEquals(1, $post->labels()->count());
        $this->assertEquals(3, $post->terms()->count());

        $post = $this->seeded['posts'][1];
        $this->assertEquals(1, $post->categories()->count());
        $this->assertEquals(0, $post->tags()->count());
        $this->assertEquals(1, $post->labels()->count());
        $this->assertEquals(1, $post->terms()->count());
    }

    public function testBelongsToManySyncAll()
    {
        $post = $this->seeded['posts'][0];

        $this->assertEquals(1, $post->categories()->count());
        $this->assertEquals(1, $post->labels()->count());

        $post->categories()->sync([
            $this->seeded['categories'][0]->id,
            $this->seeded['categories'][1]->id,
        ]);
        $post->labels()->sync([
            $this->seeded['labels'][0]->id,
            $this->seeded['labels'][1]->id,
        ]);

        $this->assertEquals(2, $post->categories()->count());
        $this->assertEquals(2, $post->labels()->count());
    }

    public function testBelongsToManySyncTags()
    {
        $post = $this->seeded['posts'][0];

        $this->assertEquals(1, $post->labels()->count());
        $this->assertEquals(2, $post->tags()->count());

        $post->labels()->detach();
        $post->tags()->sync([
            $this->seeded['tags'][0]->id,
        ]);

        $this->assertEquals(0, $post->labels()->count());
        $this->assertEquals(1, $post->tags()->count());
        $this->assertEquals($this->seeded['tags'][0]->id, $post->tags()->first()->id);

        $this->assertEquals(1, $post->terms()->count());
    }

    public function testBelongsToManySyncLabels()
    {
        $post = $this->seeded['posts'][0];

        $this->assertEquals(1, $post->labels()->count());
        $this->assertEquals(2, $post->tags()->count());

        $post->labels()->sync([
            $this->seeded['labels'][0]->id,
            $this->seeded['labels'][1]->id,
        ]);
        $post->tags()->detach();

        $this->assertEquals(2, $post->labels()->count());
        $this->assertEquals(0, $post->tags()->count());
        $this->assertEquals([
            $this->seeded['labels'][0]->id,
            $this->seeded['labels'][1]->id,
        ], $post->labels()->pluck('id')->toArray());

        $this->assertEquals(2, $post->terms()->count());
    }

    public function testBelongsToManyDetach()
    {
        $post = $this->seeded['posts'][0];

        $post->labels()->detach();
        $post->tags()->detach();

        $this->assertEquals(0, $post->labels()->count());
        $this->assertEquals(0, $post->tags()->count());
        $this->assertEquals(0, $post->terms()->count());
    }

    public function testBelongsToManyDetachOneTag()
    {
        $post = $this->seeded['posts'][0];

        $id = $post->tags()->get()->last()->id;
        $post->tags()->detach([$id]);

        $this->assertEquals(1, $post->labels()->count());
        $this->assertEquals(1, $post->tags()->count());
        $this->assertEquals(2, $post->terms()->count());
    }

    public function testBelongsToManyDetachAllWithScope()
    {
        $category = $this->seeded['categories'][0];
        $post = $this->seeded['posts'][0];

        $category->posts()->detach();
        $post->reloadRelations();

        $this->assertEquals(0, $category->posts()->count());
        $this->assertEquals(0, $post->categories()->count());
    }

    public function testBelongsToManyDetachAllWithScopeUnpublished()
    {
        $category = $this->seeded['categories'][1];
        $post = $this->seeded['posts'][1];

        $this->assertEquals(0, $category->posts()->count());
        $this->assertEquals(1, $post->categories()->count());

        // Post won't detach because it doesn't pass the scope ...
        $category->posts()->detach();
        $post->reloadRelations();

        $this->assertEquals(0, $category->posts()->count());
        $this->assertEquals(1, $post->categories()->count());

        // ... even when its ID is directly used.
        $category->posts()->detach([$post->id]);
        $post->reloadRelations();

        $this->assertEquals(0, $category->posts()->count());
        $this->assertEquals(1, $post->categories()->count());

        // Publish the post
        $post->published = true;
        $post->published_at = Carbon::now()->sub('minutes', 10);
        $post->save();

        $post->reloadRelations();
        $category->reloadRelations();

        $this->assertEquals(1, $category->posts()->count());
        $this->assertEquals(1, $post->categories()->count());

        // Detach post
        $category->posts()->detach();
        $post->reloadRelations();

        $this->assertEquals(0, $category->posts()->count());
        $this->assertEquals(0, $post->categories()->count());
    }

    public function testPivotData()
    {
        $data = 'My Pivot Data';
        $post = Post::first();

        $id = $post->categories()->get()->last()->id;
        $updated = $post->categories()->updateExistingPivot($id, ['data' => $data]);
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
        $this->expectException(BadMethodCallException::class);

        $morphs = new Morphs;
        $morphs->unknownRelation();
    }

    public function testDefinedMorphsRelation()
    {
        $morphs = new Morphs;
        $this->assertNotEmpty($morphs->related());
    }

    protected function createTables()
    {
        $this->db->schema()->create('posts', function ($table) {
            $table->increments('id');
            $table->string('title')->default('');
            $table->boolean('published')->nullable();
            $table->dateTime('published_at')->nullable();
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

        $this->db->schema()->create('categories', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->db->schema()->create('posts_categories', function ($table) {
            $table->primary(['post_id', 'category_id']);
            $table->unsignedInteger('post_id');
            $table->unsignedInteger('category_id');
            $table->string('data')->nullable();
            $table->timestamps();
        });

        $this->seedTables();
    }

    protected function seedTables()
    {
        $this->seeded['posts'][] = Post::create([
            'title' => 'A Post',
            'published' => true,
            'published_at' => Carbon::now()->sub('minutes', 10),
        ]);
        $this->seeded['posts'][] = Post::create([
            'title' => 'A Second Post',
            'published' => false,
            'published_at' => null
        ]);

        $this->seeded['categories'][] = Category::create([
            'name' => 'Category 1'
        ]);
        $this->seeded['categories'][] = Category::create([
            'name' => 'Category 2'
        ]);

        $this->seeded['labels'][] = Term::create(['type' => 'label', 'name' => 'Announcement']);
        $this->seeded['labels'][] = Term::create(['type' => 'label', 'name' => 'News']);

        $this->seeded['posts'][0]->labels()->attach($this->seeded['labels'][0]);
        $this->seeded['posts'][0]->categories()->attach($this->seeded['categories'][0]);
        $this->seeded['posts'][1]->labels()->attach($this->seeded['labels'][1]);
        $this->seeded['posts'][1]->categories()->attach($this->seeded['categories'][1]);

        $this->seeded['tags'][] = $this->seeded['posts'][0]->tags()->create(['type' => 'tag', 'name' => 'A Tag']);
        $this->seeded['tags'][] = $this->seeded['posts'][0]->tags()->create(['type' => 'tag', 'name' => 'Second Tag']);
    }
}

class Category extends \October\Rain\Database\Model
{
    public $table = 'categories';

    public $fillable = ['name'];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public $belongsToMany = [
        'posts' => [
            Post::class,
            'table' => 'posts_categories',
            'order' => 'published_at desc',
            'scope' => 'isPublished'
        ]
    ];
}

class Post extends \October\Rain\Database\Model
{
    public $table = 'posts';

    public $fillable = ['title', 'published', 'published_at'];

    protected $dates = [
        'created_at',
        'updated_at',
        'published_at',
    ];

    public $belongsToMany = [
        'categories' => [
            Category::class,
            'table' => 'posts_categories',
            'order' => 'name',
            'pivot' => ['data'],
        ],
        'tags' => [
            Term::class,
            'table'     => 'posts_terms',
            'key'       => 'post_id',
            'otherKey'  => 'term_id',
            'pivot'     => ['data'],
            'timestamps' => true,
            'conditions' => 'type = "tag"',
        ],
        'labels' => [
            Term::class,
            'table'     => 'posts_terms',
            'key'       => 'post_id',
            'otherKey'  => 'term_id',
            'pivot'     => ['data'],
            'timestamps' => true,
            'conditions' => 'type = "label"',
        ],
        'terms' => [
            Term::class,
            'table'     => 'posts_terms',
            'key'       => 'post_id',
            'otherKey'  => 'term_id',
            'timestamps' => true,
        ],
    ];

    public function scopeIsPublished($query)
    {
        return $query
            ->whereNotNull('published')
            ->where('published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<', Carbon::now())
        ;
    }
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
