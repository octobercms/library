<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOneThrough as HasOneThroughBase;

/**
 * HasOneThrough
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class HasOneThrough extends HasOneThroughBase
{
    use DefinedConstraints;

    /**
     * @var string The "name" of the relationship.
     */
    protected $relationName;

    /**
     * __construct a new has many relationship instance.
     */
    public function __construct(Builder $query, Model $farParent, Model $parent, $firstKey, $secondKey, $localKey, $secondLocalKey, $relationName = null)
    {
        $this->relationName = $relationName;

        parent::__construct($query, $farParent, $parent, $firstKey, $secondKey, $localKey, $secondLocalKey);

        $this->addDefinedConstraints();
    }

    /**
     * parentSoftDeletes determines whether close parent of the relation uses Soft Deletes.
     * @return bool
     */
    public function parentSoftDeletes()
    {
        $uses = class_uses_recursive(get_class($this->parent));

        return in_array(\October\Rain\Database\Traits\SoftDelete::class, $uses) ||
            in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, $uses);
    }
}
