<?php namespace October\Rain\Database\Concerns;

use Closure;

/**
 * HasEagerLoadAttachRelation eagerly loads all attachments on a model in one pass.
 * Since they share a common type and database table, multiple attachment definitions
 * can be eagerly loaded as a single query.
 */
trait HasEagerLoadAttachRelation
{
    /**
     * @var array eagerLoadAttachResultCache
     */
    protected $eagerLoadAttachResultCache = [];

    /**
     * eagerLoadAttachRelation eagerly loads an attachment relationship on a set of models.
     * @param  array  $models
     * @param  string  $name
     * @param  \Closure  $constraints
     * @return array|null
     */
    protected function eagerLoadAttachRelation(array $models, $name, Closure $constraints)
    {
        if (!$this->canCombineEagerLoadAttachRelation($name)) {
            return null;
        }

        $relation = $this->getRelation($name);
        $relatedModel = $this->getAttachRelatedClass($name);

        // Combine the requested attachments that share this related model in one query,
        // constrained to their field names so unrequested attachments are not hydrated.
        if (!isset($this->eagerLoadAttachResultCache[$relatedModel])) {
            $fields = array_filter(array_keys($this->getEagerLoads()), function ($field) use ($relatedModel) {
                return !str_contains($field, '.')
                    && $this->canCombineEagerLoadAttachRelation($field)
                    && $this->getAttachRelatedClass($field) === $relatedModel;
            });

            $relation->addCommonEagerConstraints($models);
            $relation->whereIn('field', array_values($fields));

            // Note this takes first constraint only. If it becomes a problem one solution
            // could be to compare the md5 of toSql() to ensure uniqueness. The workaround
            // for this edge case is to set combineEager => false in the definition.
            $constraints($relation);

            $this->eagerLoadAttachResultCache[$relatedModel] = $relation->getEager();
        }

        $results = $this->eagerLoadAttachResultCache[$relatedModel];

        return $relation->match(
            $relation->initRelation($models, $name),
            $results->where('field', $name)->values(),
            $name
        );
    }

    /**
     * canCombineEagerLoadAttachRelation checks whether an attachment can share a query.
     * Complex lookups and explicit opt-outs use Laravel's normal eager loading path.
     */
    protected function canCombineEagerLoadAttachRelation(string $name): bool
    {
        if (!in_array($this->getModel()->getRelationType($name), ['attachOne', 'attachMany'])) {
            return false;
        }

        $definition = $this->getModel()->getRelationDefinition($name);

        return !isset($definition['conditions'])
            && !isset($definition['scope'])
            && ($definition['combineEager'] ?? true) !== false;
    }

    /**
     * getAttachRelatedClass returns the related model class from an attachment definition.
     */
    protected function getAttachRelatedClass(string $name): string
    {
        return $this->getModel()->getRelationDefinition($name)[0];
    }
}
