<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Relations\Relation as RelationBase;

/**
 * Relation is an umbrella class for Laravel.
 *
 *     Relation::morphMap([
 *         'posts' => 'App\Post',
 *         'videos' => 'App\Video',
 *     ]);
 *
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
abstract class Relation extends RelationBase
{
}
