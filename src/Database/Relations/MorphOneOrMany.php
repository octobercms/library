<?php namespace October\Rain\Database\Relations;

use Illuminate\Support\Facades\Db;
use Illuminate\Database\Eloquent\Model;

trait MorphOneOrMany
{
    use DeferOneOrMany;

    /**
     * @var string The "name" of the relationship.
     */
    protected $relationName;

    /**
     * Save the supplied related model with deferred binding support.
     */
    public function save(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            return parent::save($model);
        }

        $this->add($model, $sessionKey);
        return $model->save() ? $model : false;
    }

    /**
     * Create a new instance of this related model with deferred binding support.
     */
    public function create(array $attributes = [], $sessionKey = null)
    {
        $model = parent::create($attributes);

        if ($sessionKey !== null)
            $this->add($model, $sessionKey);

        return $model;
    }

    /**
     * Adds a model to this relationship type.
     */
    public function add(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            $model->setAttribute($this->getForeignKeyName(), $this->getParentKey());
            $model->setAttribute($this->getMorphType(), $this->morphClass);
            $model->save();

            /*
             * Use the opportunity to set the relation in memory
             */
            if ($this instanceof MorphOne) {
                $this->parent->setRelation($this->relationName, $model);
            }
            else {
                $this->parent->reloadRelations($this->relationName);
            }
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

            $options = $this->parent->getRelationDefinition($this->relationName);

            if (array_get($options, 'delete', false)) {
                $model->delete();
            }
            else {
                /*
                 * Make this model an orphan ;~(
                 */
                $model->setAttribute($this->getForeignKeyName(), null);
                $model->setAttribute($this->getMorphType(), null);
                $model->save();
            }

            /*
             * Use the opportunity to set the relation in memory
             */
            if ($this instanceof MorphOne) {
                $this->parent->setRelation($this->relationName, null);
            }
            else {
                $this->parent->reloadRelations($this->relationName);
            }
        }
        else {
            $this->parent->unbindDeferred($this->relationName, $model, $sessionKey);
        }
    }

}
