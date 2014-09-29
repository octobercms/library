<?php namespace October\Rain\Database\Relations;

use October\Rain\Support\Facades\DbDongle;
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
                    ->whereRaw(DbDongle::cast('slave_id', 'INTEGER').' = '.$this->related->getQualifiedKeyName())
                    ->where('master_field', $this->relationName)
                    ->where('master_type', get_class($this->parent))
                    ->where('session_key', $sessionKey)
                    ->where('is_bind', true);
            });
        });

        // Unbind (Remove)
        $newQuery->whereNotExists(function($query) use ($sessionKey) {
            $query->from('deferred_bindings')
                ->whereRaw(DbDongle::cast('slave_id', 'INTEGER').' = '.$this->related->getQualifiedKeyName())
                ->where('master_field', $this->relationName)
                ->where('master_type', get_class($this->parent))
                ->where('session_key', $sessionKey)
                ->where('is_bind', false)
                ->whereRaw(DbDongle::parse('id > ifnull((select max(id) from deferred_bindings where
                        '.DbDongle::cast('slave_id', 'INTEGER').' = '.$this->related->getQualifiedKeyName().' and
                        master_field = ? and
                        master_type = ? and
                        session_key = ? and
                        is_bind = ?
                    ), 0)'), [
                    $this->relationName,
                    get_class($this->parent),
                    $sessionKey,
                    true
                ]);
        });

        $modelQuery->setQuery($newQuery);
        $modelQuery = $this->related->applyGlobalScopes($modelQuery);
        return $this->query = $modelQuery;
    }
}