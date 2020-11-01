<?php namespace October\Rain\Database\Relations;

trait BelongsOrMorphTo
{
    /**
     * Associate the model instance to the given parent.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function associate($model)
    {
        /**
         * @event model.relation.beforeAssociate
         * Called before associating a relation to the model (only for BelongsTo/MorphTo relations)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.beforeAssociate', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
         *         if ($relationName === 'dummyRelation') {
         *             throw new \Exception("Invalid relation!");
         *         }
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.beforeAssociate', [$this->relationName, $this->related]);

        $result = parent::associate($model);

        /**
         * @event model.relation.afterAssociate
         * Called after associating a relation to the model (only for BelongsTo/MorphTo relations)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.afterAssociate', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
         *         $relatedClass = get_class($relatedModel);
         *         traceLog("Relation {$relationName} was associated to model {$relatedClass}.");
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.afterAssociate', [$this->relationName, $this->related]);

        return $result;
    }

    /**
     * Dissociate previously dissociated model from the given parent.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function dissociate()
    {
        /**
         * @event model.relation.beforeDissociate
         * Called before dissociating a relation to the model (only for BelongsTo/MorphTo relations)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.beforeDissociate', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
         *         if ($relationName === 'permanentRelation') {
         *             throw new \Exception("Cannot dissociate a permanent relation!");
         *         }
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.beforeDissociate', [$this->relationName, $this->related]);

        $result = parent::dissociate();

        /**
         * @event model.relation.afterDissociate
         * Called after dissociating a relation to the model (only for BelongsTo/MorphTo relations)
         *
         * Example usage:
         *
         *     $model->bindEvent('model.relation.afterDissociate', function (string $relationName, \October\Rain\Database\Model $relatedModel) use (\October\Rain\Database\Model $model) {
         *         $relatedClass = get_class($relatedModel);
         *         traceLog("Relation {$relationName} was dissociated from model {$relatedClass}.");
         *     });
         *
         */
        $this->parent->fireEvent('model.relation.afterDissociate', [$this->relationName, $this->related]);

        return $result;
    }
}
