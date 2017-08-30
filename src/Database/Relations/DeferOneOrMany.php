<?php namespace October\Rain\Database\Relations;

use October\Rain\Support\Facades\DbDongle;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as BelongsToManyBase;

trait DeferOneOrMany
{
    /**
     * Returns the model query with deferred bindings added
     * @return \Illuminate\Database\Query\Builder
     */
    public function withDeferred($sessionKey)
    {
        $modelQuery = $this->query;

        $newQuery = $modelQuery->getQuery()->newQuery();

        $newQuery->from($this->related->getTable());

        /*
         * No join table will be used, strip the selected "pivot_" columns
         */
        if ($this instanceof BelongsToManyBase) {
            $this->orphanMode = true;
        }

        $newQuery->where(function($query) use ($sessionKey) {

            if ($this->parent->exists) {
                if ($this instanceof BelongsToManyBase) {
                    /*
                     * Custom query for BelongsToManyBase since a "join" cannot be used
                     */
                    $query->whereExists(function($query) {
                        $query
                            ->select($this->parent->getConnection()->raw(1))
                            ->from($this->table)
                            ->whereRaw($this->getOtherKey().' = '.$this->getWithDeferredQualifiedKeyName())
                            ->where($this->getForeignKey(), $this->parent->getKey());
                    });
                }
                else {
                    /*
                     * Trick the relation to add constraints to this nested query
                     */
                    $this->query = $query;
                    $this->addConstraints();
                }

                $this->addDefinedConstraintsToQuery($this);
            }

            /*
             * Bind (Add)
             */
            $query = $query->orWhereExists(function($query) use ($sessionKey) {
                $query
                    ->select($this->parent->getConnection()->raw(1))
                    ->from('deferred_bindings')
                    ->whereRaw('slave_id = '.$this->getWithDeferredQualifiedKeyName())
                    ->where('master_field', $this->relationName)
                    ->where('master_type', get_class($this->parent))
                    ->where('session_key', $sessionKey)
                    ->where('is_bind', 1);
            });
        });

        /*
         * Unbind (Remove)
         */
        $newQuery->whereNotExists(function($query) use ($sessionKey) {
            $query
                ->select($this->parent->getConnection()->raw(1))
                ->from('deferred_bindings')
                ->whereRaw('slave_id = '.$this->getWithDeferredQualifiedKeyName())
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

        /*
         * Apply global scopes
         */
        foreach ($this->related->getGlobalScopes() as $identifier => $scope) {
            $modelQuery->withGlobalScope($identifier, $scope);
        }

        return $this->query = $modelQuery;
    }

    /**
     * Returns the related "slave id" key in a database friendly format.
     * @return string
     */
    protected function getWithDeferredQualifiedKeyName()
    {
        return DbDongle::cast(
            DbDongle::getTablePrefix() . $this->related->getQualifiedKeyName(),
            'TEXT'
        );
    }
}
