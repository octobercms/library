<?php namespace October\Rain\Database\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use October\Rain\Database\Attach\File as FileModel;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * AttachOneOrMany
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait AttachOneOrMany
{
    use DeferOneOrMany;

    /**
     * @var string relationName is the "name" of the relationship
     */
    protected $relationName;

    /**
     * @var bool public is a default value for file public or protected state
     */
    protected $public;

    /**
     * isPublic determines if the file should be flagged "public" or not
     */
    public function isPublic()
    {
        if (isset($this->public) && $this->public !== null) {
            return $this->public;
        }

        return true;
    }

    /**
     * addConstraints sets the field (relation name) constraint on the query
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
     * getRelationExistenceQuery adds the constraints for a relationship count query
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        if ($parentQuery->getQuery()->from === $query->getQuery()->from) {
            $query = $this->getRelationExistenceQueryForSelfJoin($query, $parentQuery, $columns);
        }
        else {
            $query = $query->select($columns)->whereColumn($this->getExistenceCompareKey(), '=', $this->getQualifiedParentKeyName());
        }

        $query = $query->where($this->morphType, $this->morphClass);

        return $query->where('field', $this->relationName);
    }

    /**
     * getRelationExistenceQueryForSelfRelation adds the constraints for a relationship query on the same table
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder  $parentQuery
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRelationExistenceQueryForSelfRelation(Builder $query, Builder $parentQuery, $columns = ['*'])
    {
        $query->select($columns)->from(
            $query->getModel()->getTable().' as '.$hash = $this->getRelationCountHash()
        );

        $query->getModel()->setTable($hash);

        return $query->whereColumn($hash.'.'.$this->getForeignKeyName(), '=', $this->getQualifiedParentKeyName());
    }

    /**
     * addEagerConstraints sets the field constraint for an eager load of the relation
     * @param  array  $models
     * @return void
     */
    public function addEagerConstraints(array $models)
    {
        parent::addEagerConstraints($models);

        $this->query->where('field', $this->relationName);
    }

    /**
     * addCommonEagerConstraints adds constraints without the field constraint, used to
     * eager load multiple relations of a common type.
     * @see \October\Rain\Database\Concerns\HasEagerLoadAttachRelation
     * @param  array  $models
     * @return void
     */
    public function addCommonEagerConstraints(array $models)
    {
        parent::addEagerConstraints($models);
    }

    /**
     * save the supplied related model
     */
    public function save(Model $model, $sessionKey = null)
    {
        if (!array_key_exists('is_public', $model->attributes)) {
            $model->setAttribute('is_public', $this->isPublic());
        }

        $model->setAttribute('field', $this->relationName);

        if ($sessionKey === null) {
            $this->ensureAttachOneIsSingular();
            return parent::save($model);
        }

        $this->add($model, $sessionKey);

        return $model->save() ? $model : false;
    }

    /**
     * create a new instance of this related model
     */
    public function create(array $attributes = [], $sessionKey = null)
    {
        if (!array_key_exists('is_public', $attributes)) {
            $attributes = array_merge(['is_public' => $this->isPublic()], $attributes);
        }

        $attributes['field'] = $this->relationName;

        if ($sessionKey === null) {
            $this->ensureAttachOneIsSingular();
        }

        $model = parent::create($attributes);

        if ($sessionKey !== null) {
            $this->add($model, $sessionKey);
        }

        return $model;
    }

    /**
     * createFromFile
     */
    public function createFromFile(string $filePath, array $attributes = [], $sessionKey = null)
    {
        if (!array_key_exists('is_public', $attributes)) {
            $attributes = array_merge(['is_public' => $this->isPublic()], $attributes);
        }

        $attributes['field'] = $this->relationName;

        if ($sessionKey === null) {
            $this->ensureAttachOneIsSingular();
        }

        $model = parent::make($attributes);
        $model->fromFile($filePath);
        $model->save();

        if ($sessionKey !== null) {
            $this->add($model, $sessionKey);
        }

        return $model;
    }

    /**
     * add a model to this relationship type
     */
    public function add(Model $model, $sessionKey = null)
    {
        if (!array_key_exists('is_public', $model->attributes)) {
            $model->is_public = $this->isPublic();
        }

        if ($sessionKey === null) {
            /**
             * @event model.relation.beforeAdd
             * Called before adding a relation to the model (for AttachOneOrMany, HasOneOrMany & MorphOneOrMany relations)
             *
             * Example usage:
             *
             *     $model->bindEvent('model.relation.beforeAdd', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
             *         if ($relationName === 'some_relation') {
             *             return false;
             *         }
             *     });
             *
             */
            if ($this->parent->fireEvent('model.relation.beforeAdd', [$this->relationName, $model], true) === false) {
                return;
            }

            $this->ensureAttachOneIsSingular();

            // Associate the model
            if ($this->parent->exists) {
                $model->setAttribute($this->getForeignKeyName(), $this->getParentKey());
                $model->setAttribute($this->getMorphType(), $this->morphClass);
                $model->setAttribute('field', $this->relationName);
                $model->save();
            }
            else {
                $this->parent->bindEventOnce('model.afterSave', function () use ($model) {
                    $model->setAttribute($this->getForeignKeyName(), $this->getParentKey());
                    $model->setAttribute($this->getMorphType(), $this->morphClass);
                    $model->setAttribute('field', $this->relationName);
                    $model->save();
                });
            }

            // Use the opportunity to set the relation in memory
            if ($this instanceof AttachOne) {
                $this->parent->setRelation($this->relationName, $model);
            }
            else {
                $this->parent->unsetRelation($this->relationName);
            }

            /**
             * @event model.relation.add
             * Called after adding a relation to the model (for AttachOneOrMany, HasOneOrMany & MorphOneOrMany relations)
             *
             * Example usage:
             *
             *     $model->bindEvent('model.relation.add', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
             *         $relatedClass = get_class($relatedModel);
             *         $modelClass = get_class($model);
             *         traceLog("{$relatedClass} was added as {$relationName} to {$modelClass}.");
             *     });
             *
             */
            $this->parent->fireEvent('model.relation.add', [$this->relationName, $model]);
        }
        else {
            $this->ensureAttachOneIsSingular($sessionKey);
            $this->parent->bindDeferred($this->relationName, $model, $sessionKey);
        }
    }

    /**
     * addMany attaches an array of models to the parent instance with deferred binding support
     * @param  array  $models
     */
    public function addMany($models, $sessionKey = null)
    {
        foreach ($models as $model) {
            $this->add($model, $sessionKey);
        }
    }

    /**
     * remove a model from this relationship type
     */
    public function remove(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            /**
             * @event model.relation.beforeRemove
             * Called before removing a relation to the model (for AttachOneOrMany, HasOneOrMany & MorphOneOrMany relations)
             *
             * Example usage:
             *
             *     $model->bindEvent('model.relation.beforeRemove', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
             *         if ($relationName === 'perm_relation') {
             *             return false;
             *         }
             *     });
             *
             */
            if ($this->parent->fireEvent('model.relation.beforeRemove', [$this->relationName, $model], true) === false) {
                return;
            }

            if (!$this->isModelRemovable($model)) {
                return;
            }

            $options = $this->parent->getRelationDefinition($this->relationName);

            if (array_get($options, 'delete', false)) {
                $model->delete();
            }
            else {
                // Make this model an orphan ;~(
                $model->setAttribute($this->getForeignKeyName(), null);
                $model->setAttribute($this->getMorphType(), null);
                $model->setAttribute('field', null);
                $model->save();
            }

            // Use the opportunity to set the relation in memory
            if ($this instanceof AttachOne) {
                $this->parent->setRelation($this->relationName, null);
            }
            else {
                $this->parent->unsetRelation($this->relationName);
            }

            /**
             * @event model.relation.remove
             * Called after removing a relation to the model (for AttachOneOrMany, HasOneOrMany & MorphOneOrMany relations)
             *
             * Example usage:
             *
             *     $model->bindEvent('model.relation.remove', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
             *         $relatedClass = get_class($relatedModel);
             *         $modelClass = get_class($model);
             *         traceLog("{$relatedClass} was removed from {$modelClass}.");
             *     });
             *
             */
            $this->parent->fireEvent('model.relation.remove', [$this->relationName, $model]);
        }
        else {
            $this->parent->unbindDeferred($this->relationName, $model, $sessionKey);
        }
    }

    /**
     * isModelRemovable returns true if an existing model is already associated
     */
    protected function isModelRemovable($model): bool
    {
        return
            ((string) $model->getAttribute($this->getForeignKeyName()) === (string) $this->getParentKey()) &&
            $model->getAttribute($this->getMorphType()) === $this->morphClass &&
            $model->getAttribute('field') === $this->relationName;
    }

    /**
     * ensureAttachOneIsSingular ensures AttachOne only has one attachment,
     * by deleting siblings for singular relations.
     */
    protected function ensureAttachOneIsSingular($sessionKey = null)
    {
        if (!$this instanceof AttachOne) {
            return;
        }

        if ($sessionKey) {
            foreach ($this->withDeferred($sessionKey)->get() as $record) {
                $this->parent->unbindDeferred($this->relationName, $record, $sessionKey);
            }
            return;
        }

        if ($this->parent->exists) {
            $this->delete();
        }
    }

    /**
     * @deprecated this method is removed in October CMS v4
     */
    public function makeValidationFile($value)
    {
        if ($value instanceof FileModel) {
            $localPath = $value->getLocalPath();

            // Exception handling for UploadedFile
            if (file_exists($localPath)) {
                return new UploadedFile(
                    $localPath,
                    $value->file_name,
                    $value->content_type,
                    null,
                    true
                );
            }

            // Fallback to string
            $value = $localPath;
        }

        /*
         * @todo `$value` might be a string, may not validate
         */

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
     * @deprecated use getForeignKeyName
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * @deprecated use getLocalKeyName
     */
    public function getOtherKey()
    {
        return $this->localKey;
    }
}
