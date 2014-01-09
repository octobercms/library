<?php namespace October\Rain\Database\Relations;

use Illuminate\Support\Facades\Db;
use Illuminate\Database\Eloquent\Model;

trait DeferOneOrMany
{
    /**
     * Save the supplied related model with deferred binding support.
     */
    public function save(Model $model, $sessionKey = null)
    {
        if ($sessionKey === null) {
            return parent::save($model);
        }
        else {
            $this->add($model, $sessionKey);

            // Save the related model and any deferred bindings it might have
            return $model->save(null, $sessionKey) ? $model : false;
        }
    }

    /**
     * Create a new instance of this related model with deferred binding support.
     */
    public function create(array $attributes, $sessionKey = null)
    {
        $model = parent::create($attributes);

        if ($sessionKey !== null)
            $this->add($model, $sessionKey);

        return $model;
    }

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