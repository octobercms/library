<?php namespace October\Rain\Database\Relations;

use October\Rain\Support\Facades\DbDongle;
use Illuminate\Support\Facades\Db;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as BelongsToManyBase;

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

        // A left join so the ON constraint is not strict
        if ($this instanceof BelongsToManyBase) {
            $this->setLeftJoin($newQuery);
        }

        $newQuery->where(function($query) use ($sessionKey) {

            // Trick the relation to add constraints to this nested query
            if ($this->parent->exists) {
                $this->query = $query;
                if ($this instanceof BelongsToManyBase) {
                    $this->setWhere();
                }
                else {
                    $this->addConstraints();
                }
            }

            // Bind (Add)
            $query = $query->orWhereExists(function($query) use ($sessionKey) {
                $query->from('deferred_bindings')
                    ->whereRaw(DbDongle::cast('slave_id', 'INTEGER').' = '.DbDongle::getTablePrefix().$this->related->getQualifiedKeyName())
                    ->where('master_field', $this->relationName)
                    ->where('master_type', get_class($this->parent))
                    ->where('session_key', $sessionKey)
                    ->where('is_bind', true);
            });
        });

        // Unbind (Remove)
        $newQuery->whereNotExists(function($query) use ($sessionKey) {
            $query->from('deferred_bindings')
                ->whereRaw(DbDongle::cast('slave_id', 'INTEGER').' = '.DbDongle::getTablePrefix().$this->related->getQualifiedKeyName())
                ->where('master_field', $this->relationName)
                ->where('master_type', get_class($this->parent))
                ->where('session_key', $sessionKey)
                ->where('is_bind', false)
                ->whereRaw(DbDongle::parse('id > ifnull((select max(id) from '.DbDongle::getTablePrefix().'deferred_bindings where
                        '.DbDongle::cast('slave_id', 'INTEGER').' = '.DbDongle::getTablePrefix().$this->related->getQualifiedKeyName().' and
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