<?php

class RelationsTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->create_tables();
        $this->seed_tables();
    }

    public function create_tables()
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

    public function seed_tables()
    {
        $post = Post::create([
            'title' => 'A Post',
        ]);

        $post->tags()->create(['type'=>'tag', 'name'=>'A Tag']);
        $post->tags()->create(['type'=>'tag', 'name'=>'Second Tag']);
        $post->categories()->create(['type'=>'category', 'name'=>'A Category']);
        $post->categories()->create(['type'=>'category', 'name'=>'Second Category']);
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
