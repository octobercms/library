<?php namespace October\Rain\Database\Relations;

use Illuminate\Support\Facades\Db;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

trait AttachOneOrMany
{
    use DeferOneOrMany;

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
        // Delete siblings for single attachments
        if ($sessionKey === null && $this instanceof AttachOne)
            $this->delete();

        if (!array_key_exists('is_public', $model->attributes))
            $model->setAttribute('is_public', $this->isPublic());

        $model->setAttribute('field', $this->relationName);

        if ($sessionKey === null) {
            return parent::save($model);
        }
        else {
            $this->add($model, $sessionKey);
            return $model->save() ? $model : false;
        }
    }

    /**
     * Create a new instance of this related model.
     */
    public function create(array $attributes, $sessionKey = null)
    {
        // Delete siblings for single attachments
        if ($sessionKey === null && $this instanceof AttachOne)
            $this->delete();

        if (!array_key_exists('is_public', $attributes))
            $attributes = array_merge(['is_public' => $this->isPublic()], $attributes);

        $attributes['field'] = $this->relationName;

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
        if (!array_key_exists('is_public', $model->attributes))
            $model->is_public = $this->isPublic();

        if ($sessionKey === null) {

            // Delete siblings for single attachments
            if ($this instanceof AttachOne)
                $this->delete();

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

            $options = $this->parent->getRelationDefinition($this->relationName);

            if (array_get($options, 'delete', false)) {
                $model->delete();
            }
            else {
                // Make this model an orphan ;~(
                $model->setAttribute($this->getPlainForeignKey(), null);
                $model->setAttribute($this->getPlainMorphType(), null);
                $model->setAttribute('field', null);
                $model->save();
            }

        }
        else {
            $this->parent->unbindDeferred($this->relationName, $model, $sessionKey);
        }
    }

}