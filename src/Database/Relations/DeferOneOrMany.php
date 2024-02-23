<?php namespace October\Rain\Database\Relations;

use October\Rain\Support\Facades\DbDongle;
use October\Rain\Database\Relations\BelongsToMany as BelongsToManyBase;

/**
 * DeferOneOrMany
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait DeferOneOrMany
{
    /**
     * withDeferred returns a new model query with deferred bindings added, this
     * will reset any constraints that come before it
     * @param string|null $sessionKey
     * @return \Illuminate\Database\Query\Builder
     */
    public function withDeferred($sessionKey = null)
    {
        $newQuery = $this->query->getQuery()->newQuery();
        $newQuery->from($this->related->getTable());

        // Readd the defined constraints
        $this->addDefinedConstraintsToQuery($newQuery);

        // Apply deferred binding to the new query
        $newQuery = $this->withDeferredQuery($newQuery, $sessionKey);

        // Bless this query with the deferred query
        $this->query->setQuery($newQuery);

        // Readd the global scopes
        foreach ($this->related->getGlobalScopes() as $identifier => $scope) {
            $this->query->withGlobalScope($identifier, $scope);
        }

        return $this->query;
    }

    /**
     * withDeferredQuery returns the supplied model query, or current model query, with
     * deferred bindings added, this will preserve any constraints that came before it
     * @param \Illuminate\Database\Query\Builder|null $newQuery
     * @param string|null $sessionKey
     * @return \Illuminate\Database\Query\Builder
     */
    public function withDeferredQuery($newQuery = null, $sessionKey = null)
    {
        if ($newQuery === null) {
            $newQuery = $this->query->getQuery();
        }

        // Guess the key from the parent model
        if ($sessionKey === null) {
            $sessionKey = $this->parent->sessionKey;
        }

        // Swap the standard inner join for a left join
        if ($this instanceof BelongsToManyBase) {
            $this->performLeftJoin($newQuery);
            $this->performSortableColumnJoin($newQuery, $sessionKey);
        }

        $newQuery->where(function ($query) use ($sessionKey) {
            // Trick the relation to add constraints to this nested query
            if ($this->parent->exists) {
                $oldQuery = $this->query;
                $this->query = $query;
                $this->addConstraints();
                $this->query = $oldQuery;
            }

            // Bind (Add)
            $query = $query->orWhereIn($this->related->getQualifiedKeyName(), function ($query) use ($sessionKey) {
                $query
                    ->select('slave_id')
                    ->from('deferred_bindings')
                    ->where('master_field', $this->relationName)
                    ->where('master_type', get_class($this->parent))
                    ->where('session_key', $sessionKey)
                    ->where('is_bind', 1);
            });
        });

        // Unbind (Remove)
        $newQuery->whereNotIn($this->related->getQualifiedKeyName(), function ($query) use ($sessionKey) {
            $query
                ->select('slave_id')
                ->from('deferred_bindings')
                ->where('master_field', $this->relationName)
                ->where('master_type', get_class($this->parent))
                ->where('session_key', $sessionKey)
                ->where('is_bind', 0)
                ->whereRaw(DbDongle::parse('id > ifnull((select max(id) from '.DbDongle::getTablePrefix().'deferred_bindings where
                        slave_id = '.$this->getWithDeferredQualifiedKeyName().' and
                        master_field = ? and
                        master_type = ? and
                        session_key = ? and
                        is_bind = ?
                    ), 0)'), [
                    $this->relationName,
                    get_class($this->parent),
                    $sessionKey,
                    1
                ]);
        });

        return $newQuery;
    }

    /**
     * getWithDeferredQualifiedKeyName returns the related "slave id" key
     * in a database friendly format.
     * @return \Illuminate\Database\Query\Expression
     */
    protected function getWithDeferredQualifiedKeyName()
    {
        return DbDongle::rawValue(
            DbDongle::getTablePrefix() . $this->related->getQualifiedKeyName()
        );
    }
}
