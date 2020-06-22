<?php namespace October\Rain\Database\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Concerns\QueriesRelationships as IlluminateQueriesRelationships;
use Illuminate\Support\Str;

/**
 * @mixin IlluminateQueriesRelationships
 */
trait QueriesRelationships
{
    use IlluminateQueriesRelationships {
        IlluminateQueriesRelationships::withCount as IlluminateWithCount;
    }

    /**
     * Add subselect queries to count the relations.
     *
     * @param  mixed  $relations
     * @return \October\Rain\Database\Eloquent\Concerns\QueriesRelationships
     */
    public function withCount($relations)
    {
        if (empty($relations)) {
            return $this;
        }

        if (is_null($this->query->columns)) {
            $this->query->select([$this->query->from.'.*']);
        }

        $relations = is_array($relations) ? $relations : func_get_args();

        foreach ($this->parseWithRelations($relations) as $name => $constraints) {
            // First we will determine if the name has been aliased using an "as" clause on the name
            // and if it has we will extract the actual relationship name and the desired name of
            // the resulting column. This allows multiple counts on the same relationship name.
            $segments = explode(' ', $name);

            unset($alias);

            if (count($segments) === 3 && Str::lower($segments[1]) === 'as') {
                list($name, $alias) = [$segments[0], $segments[2]];
            }

            $relation = $this->getRelationWithoutConstraints($name);

            // Here we will get the relationship count query and prepare to add it to the main query
            // as a sub-select. First, we'll get the "has" query and use that to get the relation
            // count query. We will normalize the relation name then append _count as the name.
            $query = $relation->getRelationExistenceCountQuery(
                $relation->getRelated()->newQuery(), $this
            );

            $query->callScope($constraints);

            $query = $query->mergeConstraintsFrom($relation->getQuery())->toBase();

            $query->orders = null;
            $query->setBindings([], 'order');

            // Finally we will add the proper result column alias to the query and run the subselect
            // statement against the query builder. Then we will return the builder instance back
            // to the developer for further constraint chaining that needs to take place on it.
            $column = $alias ?? Str::snake($name.'_count');

            $this->selectSub($query, $column);
        }

        return $this;
    }
}