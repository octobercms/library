<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasManyThrough as HasManyThroughBase;

class HasManyThrough extends HasManyThroughBase
{
    /**
     * @var string The "name" of the relationship.
     */
    protected $relationName;

    /**
     * Create a new has many relationship instance.
     * @return void
     */
    public function __construct(Builder $query, Model $farParent, Model $parent, $firstKey, $secondKey, $relationName = null)
    {
        $this->relationName = $relationName;

        parent::__construct($query, $farParent, $parent, $firstKey, $secondKey);
    }
}