<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use October\Rain\Support\Facades\DbDongle;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait AttachOneOrMany
{
    use DeferOneOrMany;

    /**
     * @var string The "name" of the relationship.
     */
    protected $relationName;

    /**
     * @var boolean Default value for file public or protected state.
     */
    protected $public;

    /**
     * Determines if the file should be flagged "public" or not.
     */
    public function isPublic()
    {
        if (isset($this->public) && $this->public !== null) {
            return $this->public;
        }

        return true;
    }

    /**
     * Set the field (relation name) constraint on the query.
     * @return void
     */
    public function addConstraints()
    {
        if (static::$constraints) {
            $this->query->where($this->morphType, $this->morphClass);

            $this->query->where($this->foreignKey, '=', $this->getParentKey());

            $this->query->where('field', $this->relationName);

            $this->query->whereNotNull($this->foreignKey);
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
        if ($parent->getQuery()->from == $query->getQuery()->from) {
            $query = $this->getRelationCountQueryForSelfRelation($query, $parent);
        }
        else {
            $query->select(new Expression('count(*)'));

            $key = DbDongle::cast($this->wrap($this->getQualifiedParentKeyName()), 'TEXT');

            $query = $query->where($this->getHasCompareKey(), '=', new Expression($key));
        }

        $query = $query->where($this->morphType, $this->morphClass);

        return $query->where('field', $this->relationName);
    }

    /**
     * Add the constraints for a relationship count query on the same table.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parent
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationCountQueryForSelfRelation(Builder $query, Builder $parent)
    {
        $query->select(new Expression('count(*)'));

        $query->from($query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash());

        $query->getModel()->setTable($hash);

        $key = DbDongle::cast($this->wrap($this->getQualifiedParentKeyName()), 'TEXT');

        return $query->where($hash.'.'.$this->getPlainForeignKey(), '=', new Expression($key));
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
        if ($sessionKey === null && $this instanceof AttachOne) {
            $this->delete();
        }

        if (!array_key_exists('is_public', $model->attributes)) {
            $model->setAttribute('is_public', $this->isPublic());
        }

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
    public function create(array $attributes = [], $sessionKey = null)
    {
        // Delete siblings for single attachments
        if ($sessionKey === null && $this instanceof AttachOne) {
            $this->delete();
        }

        if (!array_key_exists('is_public', $attributes)) {
            $attributes = array_merge(['is_public' => $this->isPublic()], $attributes);
        }

        $attributes['field'] = $this->relationName;

        $model = parent::create($attributes);

        if ($sessionKey !== null) {
            $this->add($model, $sessionKey);
        }

        return $model;
    }

    /**
     * Adds a model to this relationship type.
     */
    public function add(Model $model, $sessionKey = null)
    {
        if (!array_key_exists('is_public', $model->attributes)) {
            $model->is_public = $this->isPublic();
        }

        if ($sessionKey === null) {

            // Delete siblings for single attachments
            if ($this instanceof AttachOne) {
                $this->delete();
            }

            $model->setAttribute($this->getPlainForeignKey(), $this->parent->getKey());
            $model->setAttribute($this->getPlainMorphType(), $this->morphClass);
            $model->setAttribute('field', $this->relationName);
            $model->save();

            /*
             * Use the opportunity to set the relation in memory
             */
            if ($this instanceof AttachOne) {
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
                $model->setAttribute($this->getPlainForeignKey(), null);
                $model->setAttribute($this->getPlainMorphType(), null);
                $model->setAttribute('field', null);
                $model->save();
            }

            /*
             * Use the opportunity to set the relation in memory
             */
            if ($this instanceof AttachOne) {
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

    /**
     * Returns true if the specified value can be used as the data attribute.
     */
    protected function isValidFileData($value)
    {
        if ($value instanceof UploadedFile) {
            return true;
        }

        if (is_string($value) && file_exists($value)) {
            return true;
        }

        return false;
    }
}
