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
     * withDeferred returns the model query with deferred bindings added
     * @param string|null $sessionKey
     * @return \Illuminate\Database\Query\Builder
     */
    public function withDeferred($sessionKey = null)
    {
        $modelQuery = $this->query;

        $newQuery = $modelQuery->getQuery()->newQuery();

        $newQuery->from($this->related->getTable());

        // Guess the key from the parent model
        if ($sessionKey === null) {
            $sessionKey = $this->parent->sessionKey;
        }

        // Swap the standard inner join for a left join
        if ($this instanceof BelongsToManyBase) {
            $this->performLeftJoin($newQuery);
        }

        $newQuery->where(function ($query) use ($sessionKey) {
            if ($this->parent->exists) {
                $this->query = $query;
                $this->addConstraints();

                if (!$this instanceof BelongsToManyBase) {
                    $this->addDefinedConstraintsToQuery($this);
                }
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

        $modelQuery->setQuery($newQuery);

        // Apply global scopes
        foreach ($this->related->getGlobalScopes() as $identifier => $scope) {
            $modelQuery->withGlobalScope($identifier, $scope);
        }

        return $this->query = $modelQuery;
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
