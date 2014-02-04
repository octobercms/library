<?php namespace October\Rain\Database\Relations;

use Illuminate\Support\Facades\Db;
use October\Rain\Database\Model;

trait MorphOneOrMany
{
    use DeferOneOrMany;

    /**
     * Adds a model to this relationship type.
     */
    public function add(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            $model->setAttribute($this->getPlainForeignKey(), $this->parent->getKey());
            $model->setAttribute($this->getPlainMorphType(), $this->morphClass);
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

            // Make this model an orphan ;~(
            $model->setAttribute($this->getPlainForeignKey(), null);
            $model->setAttribute($this->getPlainMorphType(), null);
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

        // @todo Join everything that has my foreign key in the other table
        // with constraints

        return $this;
    }

}