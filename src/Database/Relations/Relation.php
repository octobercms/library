<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Relations\Relation as RelationBase;

/**
 * Umbrella class for Laravel.
 *
 *     Relation::morphMap([
 *         'posts' => 'App\Post',
 *         'videos' => 'App\Video',
 *     ]);
 *
 */
abstract class Relation extends RelationBase
{
}
