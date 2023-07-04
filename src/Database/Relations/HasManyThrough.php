<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasManyThrough as HasManyThroughBase;

/**
 * HasManyThrough class extension
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class HasManyThrough extends HasManyThroughBase
{
    use DefinedConstraints;

    /**
     * @var string relationName
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
     * {@inheritDoc}
     */
    public function addDefinedConstraints()
    {
        $args = $this->farParent->getRelationDefinition($this->relationName);

        $this->addDefinedConstraintsToRelation($this, $args);

        $this->addDefinedConstraintsToQuery($this, $args);
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

    /**
     * getSimpleValue is a helper for getting this relationship simple value,
     * generally useful with form values.
     */
    public function getSimpleValue()
    {
        $value = null;
        $relationName = $this->relationName;

        if ($this->farParent->relationLoaded($relationName)) {
            $value = $this->farParent->getRelation($relationName)
                ->pluck($this->getRelatedKeyName())
                ->all()
            ;
        }
        else {
            $value = $this->query->getQuery()
                ->pluck($this->getQualifiedRelatedKeyName())
                ->all()
            ;
        }

        return $value;
    }

    /**
     * getRelatedKeyName
     * @return string
     */
    public function getRelatedKeyName()
    {
        return $this->related->getKeyName();
    }

    /**
     * getQualifiedRelatedKeyName
     * @return string
     */
    public function getQualifiedRelatedKeyName()
    {
        return $this->related->getQualifiedKeyName();
    }
}
