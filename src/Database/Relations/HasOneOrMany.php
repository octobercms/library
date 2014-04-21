<?php namespace October\Rain\Database\Relations;

use Illuminate\Support\Facades\Db;
use October\Rain\Database\Model;

trait HasOneOrMany
{
    use DeferOneOrMany;

    /**
     * Adds a model to this relationship type.
     */
    public function add(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            $model->setAttribute($this->getPlainForeignKey(), $this->parent->getKey());
            $model->save();
        }
        else {
            $this->parent->bindDeferred($this->relationName, $model, $sessionKey);
        }
    }

    /**
     * Removes a model from this relationship type.
     */
    public function remove(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            $model->setAttribute($this->getPlainForeignKey(), null);
            $model->save();
        }
        else {
            $this->parent->unbindDeferred($this->relationName, $model, $sessionKey);
        }
    }

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
        $otherKey = $this->parent->getTable().'.'.$this->related->getKeyName();
        $query->leftJoin($otherTable, $this->foreignKey, '=', $otherKey);

        return $this;
    }
}