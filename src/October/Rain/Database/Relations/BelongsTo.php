<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsTo as BelongsToBase;

class BelongsTo extends BelongsToBase
{

    /**
     * Joins the relationship tables to a query as a LEFT JOIN.
     */
    public function joinWithQuery($query)
    {
        $query = $query ?: $this->query;

        /*
         * Join the 'other' relation table
         */
        $otherTable = $this->related->getTable();
        $otherKey = $otherTable.'.'.$this->related->getKeyName();
        $foreignKey = $this->parent->getTable().'.'.$this->foreignKey;

        $query->leftJoin($otherTable, $otherKey, '=', $foreignKey);

        return $this;
    }

}