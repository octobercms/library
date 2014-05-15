<?php namespace October\Rain\Database\Relations;

use Illuminate\Support\Facades\Db;
use Illuminate\Database\Eloquent\Model;

trait DeferOneOrMany
{
    /**
     * Returns the model query with deferred bindings added
     */
    public function withDeferred($sessionKey)
    {
        $modelQuery = $this->query;
        $newQuery = $modelQuery->getQuery()->newQuery();

        $newQuery->from($this->related->getTable());
        $newQuery->where(function($query) use ($sessionKey) {

            // Trick the relation to add constraints to this nested query
            if ($this->parent->exists) {
                $this->query = $query;
                $this->addConstraints();
            }

            // Bind (Add)
            $query = $query->orWhereExists(function($query) use ($sessionKey) {
                $query->from('deferred_bindings')
                    ->whereRaw('slave_id = '. $this->related->getQualifiedKeyName())
                    ->where('master_field', $this->relationName)
                    ->where('master_type', get_class($this->parent))
                    ->where('session_key', $sessionKey)
                    ->where('bind', true);
            });
        });

        // Unbind (Remove)
        $newQuery->whereNotExists(function($query) use ($sessionKey) {
            $query->from('deferred_bindings')
                ->whereRaw('slave_id = '. $this->related->getQualifiedKeyName())
                ->where('master_field', $this->relationName)
                ->where('master_type', get_class($this->parent))
                ->where('session_key', $sessionKey)
                ->where('bind', false)
                ->whereRaw('id > ifnull((select max(id) from deferred_bindings where
                        slave_id = '.$this->related->getQualifiedKeyName().' and
                        master_field = ? and
                        master_type = ? and
                        session_key = ? and
                        bind = ?
                    ), 0)', [
                    $this->relationName,
                    get_class($this->parent),
                    $sessionKey,
                    true
                ]);
        });

        $modelQuery->setQuery($newQuery);
        return $this->query = $modelQuery;
    }
}