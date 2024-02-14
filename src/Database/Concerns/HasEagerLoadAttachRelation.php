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
     * @param  string  $relatedModel
     * @param  array  $models
     * @param  string  $name
     * @param  \Closure  $constraints
     * @return array|null
     */
    protected function eagerLoadAttachRelation(array $models, $name, Closure $constraints)
    {
        // Look up relation type
        $relationType = $this->getModel()->getRelationType($name);
        if (!$relationType || !in_array($relationType, ['attachOne', 'attachMany'])) {
            return null;
        }

        // Only vanilla attachments are supported, pass complex lookups back to Laravel
        $definition = $this->getModel()->getRelationDefinition($name);
        if (isset($definition['conditions']) || isset($definition['scope'])) {
            return null;
        }

        // Opt-out of the combined eager loading logic
        if (isset($definition['combineEager']) && $definition['combineEager'] === false) {
            return null;
        }

        $relation = $this->getRelation($name);
        $relatedModel = get_class($relation->getRelated());

        // Perform a global look up attachment without the 'field' constraint
        // to produce a combined subset of all possible attachment relations.
        if (!isset($this->eagerLoadAttachResultCache[$relatedModel])) {
            $relation->addCommonEagerConstraints($models);

            // Note this takes first constraint only. If it becomes a problem one solution
            // could be to compare the md5 of toSql() to ensure uniqueness. The workaround
            // for this edge case is to set combineEager => false in the definition.
            $constraints($relation);

            $this->eagerLoadAttachResultCache[$relatedModel] = $relation->getEager();
        }

        $results = $this->eagerLoadAttachResultCache[$relatedModel];

        return $relation->match(
            $relation->initRelation($models, $name),
            $results->where('field', $name),
            $name
        );
    }
}
