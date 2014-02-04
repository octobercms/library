<?php namespace October\Rain\Database\Relations;

use Illuminate\Support\Facades\Db;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

trait AttachOneOrMany
{
    use DeferOneOrMany {
        DeferOneOrMany::save as saveDefer;
        DeferOneOrMany::create as createDefer;
    }

    /**
     * Determines if the file should be flagged "public" or not.
     */
    public function isPublic()
    {
        if (isset($this->public) && $this->public !== null)
            return $this->public;

        return true;
    }

    /**
     * Set the field (relation name) constraint on the query.
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            parent::addConstraints();

            $this->query->where('field', $this->relationName);
        }
    }

    /**
     * Add the field constraint for a relationship count query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parent
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationCountQuery(Builder $query, Builder $parent)
    {
        $query = parent::getRelationCountQuery($query, $parent);

        return $query->where('field', $this->relationName);
    }

    /**
     * Set the field constraint for an eager load of the relation.
     *
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        parent::addEagerConstraints($models);

        $this->query->where('field', $this->relationName);
    }

    /**
     * Save the supplied related model.
     */
    public function save(Model $model, $sessionKey = null)
    {
        if (!array_key_exists('public', $model->attributes))
            $model->setAttribute('public', $this->isPublic());

        $model->setAttribute('field', $this->relationName);

        return $this->saveDefer($model, $sessionKey);
    }

    /**
     * Create a new instance of this related model.
     */
    public function create(array $attributes, $sessionKey = null)
    {
        if (!array_key_exists('public', $attributes))
            $attributes = array_merge(['public' => $this->isPublic()], $attributes);

        $attributes['field'] = $this->relationName;

        return $this->createDefer($attributes, $sessionKey);
    }

    /**
     * Adds a model to this relationship type.
     */
    public function add(Model $model, $sessionKey = null)
    {
        if (!array_key_exists('public', $model->attributes))
            $model->public = $this->isPublic();

        if ($sessionKey === null) {
            $model->setAttribute($this->getPlainForeignKey(), $this->parent->getKey());
            $model->setAttribute($this->getPlainMorphType(), $this->morphClass);
            $model->setAttribute('field', $this->relationName);
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
            $model->setAttribute('field', null);
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