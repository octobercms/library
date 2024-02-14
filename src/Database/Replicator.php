<?php namespace October\Rain\Database;

use October\Rain\Support\Arr;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Collection as CollectionBase;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;

/**
 * Replicator service to duplicating and replicating model records
 */
class Replicator
{
    /**
     * @var \Model model context
     */
    protected $model;

    /**
     * @var bool isDuplicating the record or just returning a non-existing instance
     */
    protected $isDuplicating = false;

    /**
     * @var bool isMultisite context
     */
    protected $isMultisite = false;

    /**
     * @var array associationMap from original record to newly created record
     */
    protected $associationMap = [];

    /**
     * __construct
     */
    public function __construct($model)
    {
        $this->model = $model;
        $this->isMultisite = $model->isClassInstanceOf(\October\Contracts\Database\MultisiteInterface::class);
    }

    /**
     * replicate replicates the model into a new, non-existing instance,
     * including replicating relations.
     *
     * @param  array|null  $except
     * @return static
     */
    public function replicate(array $except = null)
    {
        $this->isDuplicating = false;

        return $this->replicateRelationsInternal($except);
    }

    /**
     * duplicate replicates a model with special multisite duplication logic.
     * To avoid duplication of has many relations, the logic only propagates relations on
     * the parent model since they are shared via site_root_id beyond this point.
     *
     * @param  array|null  $except
     * @return static
     */
    public function duplicate(array $except = null)
    {
        $this->isDuplicating = true;

        return $this->replicateRelationsInternal($except);
    }

    /**
     * replicateRelationsInternal
     */
    protected function replicateRelationsInternal(array $except = null)
    {
        $defaults = [
            $this->model->getKeyName(),
            $this->model->getCreatedAtColumn(),
            $this->model->getUpdatedAtColumn(),
        ];

        if ($this->isMultisite) {
            $defaults[] = 'site_root_id';
        }

        $attributes = Arr::except(
            $this->model->attributes,
            $except ? array_unique(array_merge($except, $defaults)) : $defaults
        );

        $instance = $this->model->newReplicationInstance($attributes);

        $definitions = $this->model->getRelationDefinitions();

        foreach ($definitions as $type => $relations) {
            foreach ($relations as $name => $options) {
                if ($this->isRelationReplicable($name)) {
                    $this->replicateRelationInternal($instance->$name(), $this->model->$name);
                }
            }
        }

        return $instance;
    }

    /**
     * replicateRelationInternal on the model instance with the supplied ones
     */
    protected function replicateRelationInternal($relationObject, $models)
    {
        if ($models instanceof CollectionBase) {
            $models = $models->all();
        }
        elseif ($models instanceof EloquentModel) {
            $models = [$models];
        }
        else {
            $models = (array) $models;
        }

        $this->associationMap = [];
        foreach (array_filter($models) as $model) {
            if ($relationObject instanceof HasOneOrMany) {
                $relationObject->add($newModel = $model->replicateWithRelations());
                $this->mapAssociation($model, $newModel);
            }
            else {
                $relationObject->add($model);
            }
        }

        $relatedModel = $relationObject->getRelated();
        if ($relatedModel->isClassInstanceOf(\October\Contracts\Database\TreeInterface::class)) {
            $this->updateTreeAssociations();
        }
    }

    /**
     * isRelationReplicable determines whether the specified relation should be replicated
     * when replicateWithRelations() is called instead of save() on the model. Default: true.
     */
    protected function isRelationReplicable(string $name): bool
    {
        $relationType = $this->model->getRelationType($name);
        if ($relationType === 'morphTo') {
            return false;
        }

        // Relation is shared via propagation
        if (
            !$this->isDuplicating &&
            $this->isMultisite &&
            $this->model->isAttributePropagatable($name)
        ) {
            return false;
        }

        $definition = $this->model->getRelationDefinition($name);
        if (!array_key_exists('replicate', $definition)) {
            return true;
        }

        return (bool) $definition['replicate'];
    }

    /**
     * mapAssociation is an internal method that keeps a record of what records were created
     * and their associated source, the following format is used:
     *
     *     [FromModel::id] => [FromModel, ToModel]
     */
    protected function mapAssociation($currentModel, $replicatedModel)
    {
        $this->associationMap[$currentModel->getKey()] = [$currentModel, $replicatedModel];
    }

    /**
     * updateTreeAssociations sets new parents on the replicated records
     */
    protected function updateTreeAssociations()
    {
        foreach ($this->associationMap as $tuple) {
            [$currentModel, $replicatedModel] = $tuple;
            $newParent = $this->associationMap[$currentModel->getParentId()][1] ?? null;
            $replicatedModel->parent = $newParent;
        }
    }
}
