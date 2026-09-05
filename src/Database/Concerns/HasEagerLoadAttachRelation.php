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
     * @var array eagerLoadAttachRelationCache reuses relations inspected for grouping.
     */
    protected $eagerLoadAttachRelationCache = [];

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

        $names = array_values(array_filter(array_keys($this->getEagerLoads()), function ($name) {
            return !str_contains($name, '.') && $this->canCombineEagerLoadAttachRelation($name);
        }));

        // A builder can be reused with different models or eager loads. Start a fresh
        // combined cache at the first eligible attachment in each eager-load pass.
        if ($name === ($names[0] ?? null)) {
            $this->eagerLoadAttachResultCache = [];
            $this->eagerLoadAttachRelationCache = [];
        }

        $relation = $this->eagerLoadAttachRelationCache[$name] ??= $this->getRelation($name);
        $relatedModel = get_class($relation->getRelated());

        // Combine only requested attachment fields that use the same related model.
        if (!isset($this->eagerLoadAttachResultCache[$relatedModel])) {
            $fields = [];
            foreach ($names as $field) {
                $fieldRelation = $this->eagerLoadAttachRelationCache[$field] ??= $this->getRelation($field);
                if (get_class($fieldRelation->getRelated()) === $relatedModel) {
                    $fields[] = $field;
                }
            }

            $relation->addCommonEagerConstraints($models);
            $relation->whereIn('field', $fields);

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
