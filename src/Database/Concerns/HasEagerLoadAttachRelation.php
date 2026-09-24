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
     * @var array eagerLoadAttachRelations retains constructed relations for this pass
     */
    protected $eagerLoadAttachRelations = [];

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

        // Construct each requested relation once. Runtime hooks may choose a
        // different related class than the one declared in the definition.
        if (!$this->eagerLoadAttachRelations) {
            foreach (array_keys($this->getEagerLoads()) as $field) {
                if (!str_contains($field, '.') && $this->canCombineEagerLoadAttachRelation($field)) {
                    $this->eagerLoadAttachRelations[$field] = $this->getRelation($field);
                }
            }
        }

        $relation = $this->eagerLoadAttachRelations[$name];
        $relatedModel = get_class($relation->getRelated());

        // Combine the requested attachments that share this related model in one query,
        // constrained to their field names so unrequested attachments are not hydrated.
        if (!isset($this->eagerLoadAttachResultCache[$relatedModel])) {
            $fields = array_keys(array_filter($this->eagerLoadAttachRelations, function ($relation) use ($relatedModel) {
                return get_class($relation->getRelated()) === $relatedModel;
            }));

            $relation->addCommonEagerConstraints($models);
            $relation->whereIn($relation->getRelated()->qualifyColumn('field'), array_values($fields));

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

}
