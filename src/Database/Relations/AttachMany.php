<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany as MorphManyBase;

class AttachMany extends MorphManyBase
{
    use AttachOneOrMany;

    /**
     * @var string The "name" of the relationship.
     */
    protected $relationName;

    /**
     * @var boolean Default value for file public or protected state
     */
    protected $public;

    /**
     * Create a new has many relationship instance.
     * @return void
     */
    public function __construct(Builder $query, Model $parent, $type, $id, $isPublic, $localKey, $relationName = null)
    {
        $this->relationName = $relationName;
        $this->public = $isPublic;

        parent::__construct($query, $parent, $type, $id, $localKey);
    }
}