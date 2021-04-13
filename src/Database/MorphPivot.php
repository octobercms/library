<?php namespace October\Rain\Database;

use Illuminate\Database\Eloquent\Builder;

/**
 * MorphPivot
 *
 * This class is a carbon copy of Illuminate\Database\Eloquent\Relations\MorphPivot
 * so the base October\Rain\Database\Pivot class can be inherited
 *
 * @see Illuminate\Database\Eloquent\Relations
 */
class MorphPivot extends Pivot
{
    /**
     * @var string morphType is the type of the polymorphic relation.
     */
    protected $morphType;

    /**
     * @var string morphClass is the value of the polymorphic relation.
     */
    protected $morphClass;

    /**
     * setKeysForSaveQuery sets the keys for a save update query
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where($this->morphType, $this->morphClass);

        return parent::setKeysForSaveQuery($query);
    }

    /**
     * delete the pivot model record from the database.
     */
    public function delete()
    {
        $query = $this->getDeleteQuery();

        $query->where($this->morphType, $this->morphClass);

        return $query->delete();
    }

    /**
     * setMorphType for the pivot
     * @param  string  $morphType
     * @return $this
     */
    public function setMorphType($morphType)
    {
        $this->morphType = $morphType;

        return $this;
    }

    /**
     * setMorphClass for the pivot
     * @param  string  $morphClass
     * @return \Illuminate\Database\Eloquent\Relations\MorphPivot
     */
    public function setMorphClass($morphClass)
    {
        $this->morphClass = $morphClass;

        return $this;
    }
}
