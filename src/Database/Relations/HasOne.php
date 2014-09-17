<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne as HasOneBase;

class HasOne extends HasOneBase
{
    use HasOneOrMany;

    /**
     * @var string The "name" of the relationship.
     */
    protected $relationName;

    /**
     * Create a new has many relationship instance.
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey, $relationName = null)
    {
        $this->relationName = $relationName;

        parent::__construct($query, $parent, $foreignKey, $localKey);
    }

    /**
     * Get the results of the relationship.
     * @return mixed
     */
    public function getResults()
    {
        // New models have no possibility of having a relationship here
        // so prevent the first orphaned relation from being used.
        if (!$this->parent->exists)
            return null;

        return parent::getResults();
    }
}