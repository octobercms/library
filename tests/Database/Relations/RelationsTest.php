<?php

class RelationsTest extends TestCase
{
    public function setUp(): void
    {
        $this->capsule = $capsule = new Illuminate\Database\Capsule\Manager;
        $capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => ''
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $this->create_tables();
        $this->seed_tables();
    }

    public function create_tables()
    {
        $this->capsule->schema()->create('posts', function ($table) {
            $table->increments('id');
            $table->string('title')->default('');
            $table->timestamps();
        });

        $this->capsule->schema()->create('terms', function ($table) {
            $table->increments('id');
            $table->string('type');
            $table->string('name');
            $table->timestamps();

        });

        $this->capsule->schema()->create('posts_terms', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
            $table->unsignedInteger('term_id');
        });
    }

    public function seed_tables()
    {
        $post = Post::create([
            'title' => 'A Post',
        ]);

        $category = Term::create([
            'type' => 'category',
            'name' => 'A Category',
        ]);
        $category2 = Term::create([
            'type' => 'category',
            'name' => 'Second Category',
        ]);

        $tag = Term::create([
            'type' => 'tag',
            'name' => 'A Tag',
        ]);
        $tag2 = Term::create([
            'type' => 'tag',
            'name' => 'Second Tag',
        ]);

        $post->tags()->add($tag);
        $post->tags()->add($tag2);
        $post->categories()->add($category);
        $post->categories()->add($category2);
    }

    public function testTablesExist()
    {
        $this->assertTrue($this->capsule->schema()->hasTable('posts'));
        $this->assertTrue($this->capsule->schema()->hasTable('terms'));
        $this->assertTrue($this->capsule->schema()->hasTable('posts_terms'));
    }

    public function testTablesProperlySeeded()
    {
        $this->assertEquals(1, Post::count());
        $this->assertEquals(4, Term::count());
        $this->assertEquals(2, Term::where('type', 'tag')->count());
        $this->assertEquals(2, Term::where('type', 'category')->count());
    }

    public function testBelongsToMany()
    {
        $post = Post::first();
        $this->assertEquals(2, $post->tags()->count());
        $this->assertEquals(2, $post->categories()->count());
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
