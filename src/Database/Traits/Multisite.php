<?php namespace October\Rain\Database\Traits;

use Illuminate\Support\Arr;
use Site;
use October\Rain\Database\Scopes\MultisiteScope;
use Exception;

/**
 * Multisite trait allows for site-based models, the database
 * table should contain site_id and site_root_id keys
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait Multisite
{
    /**
     * @var array propagatable list of attributes to propagate to other sites.
     *
     *     protected $propagatable = [];
     */

    /**
     * @var bool|array propagatableSync will enforce model structures between all sites.
     * When set to `false` will disable sync, set `true` will sync between the site group.
     * The sync option allow sync to `all` sites, sites in the `group`, and sites the `locale`.
     *
     * Set to an array of options for more granular controls:
     *
     * - **sync** - logic to sync specific sites, available options: `all`, `group`, `locale`
     * - **structure** - enable the sync of tree/sortable structures, default: `true`
     * - **delete** - delete all linked records when any record is deleted, default: `true`
     *
     *     protected $propagatableSync = false;
     */

    /**
     * bootMultisite trait for a model.
     */
    public static function bootMultisite()
    {
        static::addGlobalScope(new MultisiteScope);
    }

    /**
     * initializeMultisite
     */
    public function initializeMultisite()
    {
        if (!is_array($this->propagatable)) {
            throw new Exception(sprintf(
                'The $propagatable property in %s must be an array to use the Multisite trait.',
                static::class
            ));
        }

        $this->bindEvent('model.beforeSave', [$this, 'multisiteBeforeSave']);

        $this->bindEvent('model.afterCreate', [$this, 'multisiteAfterCreate']);

        $this->bindEvent('model.saveComplete', [$this, 'multisiteSaveComplete']);

        $this->bindEvent('model.afterDelete', [$this, 'multisiteAfterDelete']);

        $this->defineMultisiteRelations();
    }

    /**
     * multisiteBeforeSave constructor event used internally
     */
    public function multisiteBeforeSave()
    {
        if (Site::hasGlobalContext()) {
            return;
        }

        $this->{$this->getSiteIdColumn()} = Site::getSiteIdFromContext();
    }

    /**
     * multisiteSaveComplete constructor event used internally
     */
    public function multisiteSaveComplete()
    {
        if ($this->getSaveOption('propagate') !== true) {
            return;
        }

        if (!$this->isMultisiteEnabled()) {
            return;
        }

        Site::withGlobalContext(function() {
            $otherModels = $this->newOtherSiteQuery()->get();
            $otherSites = $this->getMultisiteSyncSites();

            // Propagate attributes to known records
            if ($this->propagatable) {
                foreach ($otherSites as $siteId) {
                    if ($model = $otherModels->where('site_id', $siteId)->first()) {
                        $this->propagateToSite($siteId, $model);
                    }
                }
            }

            // Sync non-existent records
            if ($this->isMultisiteSyncEnabled()) {
                $missingSites = array_diff($otherSites, $otherModels->pluck('site_id')->all());
                foreach ($missingSites as $missingSite) {
                    $this->propagateToSite($missingSite);
                }
            }
        });
    }

    /**
     * multisiteAfterCreate constructor event used internally
     */
    public function multisiteAfterCreate()
    {
        if ($this->site_root_id) {
            return;
        }

        $this->site_root_id = $this->id;
        $this->newQueryWithoutScopes()
            ->where($this->getKeyName(), $this->id)
            ->update(['site_root_id' => $this->site_root_id])
        ;
    }

    /**
     * multisiteAfterDelete
     */
    public function multisiteAfterDelete()
    {
        if (!$this->isMultisiteSyncEnabled() || !$this->getMultisiteConfig('delete', true)) {
            return;
        }

        Site::withGlobalContext(function() {
            foreach ($this->getMultisiteSyncSites() as $siteId) {
                if (!$this->isModelUsingSameSite($siteId)) {
                    $this->deleteForSite($siteId);
                }
            }
        });
    }

    /**
     * defineMultisiteRelations will spin over every relation and apply propagation config
     */
    protected function defineMultisiteRelations()
    {
        foreach ($this->getRelationDefinitions() as $type => $relations) {
            foreach ($this->$type as $name => $definition) {
                if ($this->isAttributePropagatable($name)) {
                    $this->defineMultisiteRelation($name, $type);
                }
            }
        }
    }

    /**
     * canDeleteMultisiteRelation checks if a relation has the potential to be shared with
     * the current model. If there are 2 or more records in existence, then this method
     * will prevent the cascading deletion of relations.
     *
     * @see \October\Rain\Database\Concerns\HasRelationships::performDeleteOnRelations
     */
    public function canDeleteMultisiteRelation($name, $type = null): bool
    {
        // Attribute is exclusive to parent model without propagation
        if (!$this->isAttributePropagatable($name)) {
            return true;
        }

        if ($type === null) {
            $type = $this->getRelationType($name);
        }

        // Type is not supported by multisite
        if (!in_array($type, ['belongsToMany', 'morphToMany', 'morphedByMany', 'belongsTo', 'hasOne', 'hasMany', 'attachOne', 'attachMany'])) {
            return true;
        }

        // The current record counts for one so halt if we find more
        return !($this->newOtherSiteQuery()->count() > 1);
    }

    /**
     * defineMultisiteRelation will modify defined relations on this model so they share
     * their association using the shared identifier (`site_root_id`). Only these relation
     * types support relation sharing: `belongsToMany`, `morphToMany`, `morphedByMany`,
     * `belongsTo`, `hasOne`, `hasMany`, `attachOne`, `attachMany`.
     *
     * For many-to-many relations where both parent AND related models use multisite
     * (dual-multisite), the keys remain as default 'id' and propagation handles syncing
     * the correct site-specific related records.
     */
    protected function defineMultisiteRelation($name, $type = null)
    {
        if ($type === null) {
            $type = $this->getRelationType($name);
        }

        if ($type) {
            if (!is_array($this->$type[$name])) {
                $this->$type[$name] = (array) $this->$type[$name];
            }

            // Override the local key to the shared root identifier
            if (in_array($type, ['belongsToMany', 'morphToMany', 'morphedByMany'])) {
                // Check if related model also uses multisite (dual-multisite scenario)
                // In dual-multisite, keys stay as default 'id' and pivotSiteScope handles filtering
                $relatedIsMultisite = $this->isRelatedMultisite($name);
                if ($relatedIsMultisite) {
                    // Dual-multisite: pivot queries should be scoped by site_id
                    $this->$type[$name]['pivotSiteScope'] = true;
                }
                else {
                    // Single-multisite: use site_root_id to share relations across sites
                    $this->$type[$name]['parentKey'] = 'site_root_id';
                }
            }
            elseif (in_array($type, ['belongsTo', 'hasOne', 'hasMany'])) {
                $this->$type[$name]['otherKey'] = 'site_root_id';
            }
            elseif (in_array($type, ['attachOne', 'attachMany'])) {
                $this->$type[$name]['key'] = 'site_root_id';
            }
        }
    }

    /**
     * savePropagate the model, including to other sites
     * @return bool
     */
    public function savePropagate($options = null, $sessionKey = null)
    {
        return $this->saveInternal((array) $options + ['propagate' => true, 'sessionKey' => $sessionKey]);
    }

    /**
     * addPropagatable attributes for the model.
     * @param  array|string|null  $attributes
     */
    public function addPropagatable($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->propagatable = array_merge($this->propagatable, $attributes);

        foreach ($attributes as $attribute) {
            $this->defineMultisiteRelation($attribute);
        }
    }

    /**
     * isAttributePropagatable
     * @return bool
     */
    public function isAttributePropagatable($attribute)
    {
        return in_array($attribute, $this->propagatable);
    }

    /**
     * propagateToSite will save propagated fields to other records
     */
    public function propagateToSite($siteId, $otherModel = null)
    {
        if ($this->isModelUsingSameSite($siteId)) {
            return;
        }

        if ($otherModel === null) {
            $otherModel = $this->findOtherSiteModel($siteId);
        }

        // Perform propagation for existing records
        if ($otherModel->exists) {
            foreach ($this->propagatable as $name) {
                $relationType = $this->getRelationType($name);

                // Propagate local key relation
                if ($relationType === 'belongsTo') {
                    $fkName = $this->$name()->getForeignKeyName();
                    $otherModel->$fkName = $this->$fkName;
                }
                // Propagate local attribute (not a relation)
                elseif (!$relationType) {
                    $otherModel->$name = $this->$name;
                }
            }
        }

        $otherModel->save(['force' => true]);

        // Propagate many-to-many relations after save since pivot
        // records require the model to have an ID
        foreach ($this->propagatable as $name) {
            $relationType = $this->getRelationType($name);
            if (in_array($relationType, ['belongsToMany', 'morphToMany', 'morphedByMany'])) {
                $this->propagateManyToManyRelation($name, $siteId, $otherModel);
            }
        }

        return $otherModel;
    }

    /**
     * propagateManyToManyRelation propagates a many-to-many relation to another site.
     * For dual-multisite (both models use multisite), this finds the corresponding
     * related records in the target site and syncs them.
     */
    protected function propagateManyToManyRelation($name, $siteId, $otherModel)
    {
        $relation = $this->$name();
        $relatedModel = $relation->getRelated();

        // Check if related model uses multisite (dual-multisite scenario)
        $relatedIsMultisite = $relatedModel->isClassInstanceOf(\October\Contracts\Database\MultisiteInterface::class)
            && $relatedModel->isMultisiteEnabled();

        if (!$relatedIsMultisite) {
            return;
        }

        // Get related site_root_ids from current model's related records
        $relatedRootIds = $relation->pluck('site_root_id')->all();

        if (empty($relatedRootIds)) {
            // Clear relations on target if source has none
            Site::withContext($siteId, function() use ($otherModel, $name) {
                $otherModel->$name()->sync([]);
            });
            return;
        }

        // Find target site's corresponding records by site_root_id
        $targetIds = $relatedModel->newQueryWithoutScopes()
            ->whereIn('site_root_id', $relatedRootIds)
            ->where('site_id', $siteId)
            ->pluck('id')
            ->all();

        // Sync on target model within site context
        Site::withContext($siteId, function() use ($otherModel, $name, $targetIds) {
            $otherModel->$name()->sync($targetIds);
        });
    }

    /**
     * getMultisiteKey returns the root key if multisite is used
     */
    public function getMultisiteKey()
    {
        if (!$this->isMultisiteEnabled()) {
            return $this->getKey();
        }

        return $this->site_root_id ?: $this->getKey();
    }

    /**
     * isMultisiteEnabled allows for programmatic toggling
     * @return bool
     */
    public function isMultisiteEnabled()
    {
        return true;
    }

    /**
     * isRelatedMultisite checks if a related model class uses multisite.
     * This checks that multisite is enabled via the MultisiteInterface.
     */
    protected function isRelatedMultisite($name): bool
    {
        $relatedModel = $this->makeRelation($name);
        if (!$relatedModel) {
            return false;
        }

        return $relatedModel->isClassInstanceOf(\October\Contracts\Database\MultisiteInterface::class)
            && $relatedModel->isMultisiteEnabled();
    }

    /**
     * isMultisiteSyncEnabled
     */
    public function isMultisiteSyncEnabled()
    {
        if (!property_exists($this, 'propagatableSync')) {
            return false;
        }

        if (is_array($this->propagatableSync)) {
            return ($this->propagatableSync['sync'] ?? false) !== false;
        }

        return (bool) $this->propagatableSync;
    }

    /**
     * getMultisiteConfig
     */
    public function getMultisiteConfig($key, $default = null)
    {
        if (!property_exists($this, 'propagatableSync') || !is_array($this->propagatableSync)) {
            return $default;
        }

        return Arr::get($this->propagatableSync, $key, $default);
    }

    /**
     * getMultisiteSyncSites
     * @return array
     */
    public function getMultisiteSyncSites()
    {
        if ($this->getMultisiteConfig('sync') === 'all') {
            return Site::listSiteIds();
        }

        $siteId = $this->{$this->getSiteIdColumn()} ?: null;

        if ($this->getMultisiteConfig('sync') === 'locale') {
            return Site::listSiteIdsInLocale($siteId);
        }

        return Site::listSiteIdsInGroup($siteId);
    }

    /**
     * scopeApplyOtherSiteRoot is used to resolve a model using its ID or its root ID.
     * For example, finding a model using attributes from another site, or finding
     * all connected models for all sites.
     *
     * If the value is provided as a string, it must be the ID from the primary record,
     * in other words: taken from `site_root_id` not from the `id` column.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|\Illuminate\Database\Eloquent\Model $idOrModel
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApplyOtherSiteRoot($query, $idOrModel)
    {
        if ($idOrModel instanceof \Illuminate\Database\Eloquent\Model) {
            $idOrModel = $idOrModel->site_root_id ?: $idOrModel->id;
        }

        return $query->where(function($q) use ($idOrModel) {
            $q->where('id', $idOrModel);
            $q->orWhere('site_root_id', $idOrModel);
        });
    }

    /**
     * newOtherSiteQuery
     */
    public function newOtherSiteQuery()
    {
        return $this->newQueryWithoutScopes()->applyOtherSiteRoot($this);
    }

    /**
     * findForSite will locate a record for a specific site.
     */
    public function findForSite($siteId = null)
    {
        return $this
            ->newOtherSiteQuery()
            ->where($this->getSiteIdColumn(), $siteId)
            ->first();
    }

    /**
     * findOrCreateForSite
     */
    public function findOrCreateForSite($siteId = null)
    {
        $otherModel = $this->findOtherSiteModel($siteId);

        // Newly created model
        if (!$otherModel->exists) {
            $otherModel->save(['force' => true]);
        }

        // Restoring a trashed model
        if (
            $otherModel->isClassInstanceOf(\October\Contracts\Database\SoftDeleteInterface::class) &&
            $otherModel->trashed()
        ) {
            $otherModel->restore();
        }

        return $otherModel;
    }

    /**
     * findOtherSiteModel
     */
    protected function findOtherSiteModel($siteId = null)
    {
        if ($siteId === null) {
            $siteId = Site::getSiteIdFromContext();
        }

        if ($this->isModelUsingSameSite($siteId)) {
            return $this;
        }

        $otherModel = $this->findForSite($siteId);

        // Replicate without save
        if (!$otherModel) {
            $otherModel = $this->replicateWithRelations($this->getMultisiteConfig('except'));
            $otherModel->{$this->getSiteIdColumn()} = $siteId;
            $otherModel->site_root_id = $this->site_root_id ?: $this->id;
        }

        return $otherModel;
    }

    /**
     * deleteForSite runs the delete command on a model for another site, useful for cleaning
     * up records for other sites when the parent is deleted.
     */
    public function deleteForSite($siteId = null)
    {
        $otherModel = $this->findForSite($siteId);
        if (!$otherModel) {
            return;
        }

        $useSoftDeletes = $this->isClassInstanceOf(\October\Contracts\Database\SoftDeleteInterface::class);
        if ($useSoftDeletes && !$this->isSoftDelete()) {
            static::withoutEvents(function() use ($otherModel) {
                $otherModel->forceDelete();
            });
            return;
        }

        static::withoutEvents(function() use ($otherModel) {
            $otherModel->delete();
        });
    }

    /**
     * isModelUsingSameSite
     */
    protected function isModelUsingSameSite($siteId = null)
    {
        return (int) $this->{$this->getSiteIdColumn()} === (int) $siteId;
    }

    /**
     * getSiteIdColumn gets the name of the "site id" column.
     * @return string
     */
    public function getSiteIdColumn()
    {
        return defined('static::SITE_ID') ? static::SITE_ID : 'site_id';
    }

    /**
     * getQualifiedSiteIdColumn gets the fully qualified "site id" column.
     * @return string
     */
    public function getQualifiedSiteIdColumn()
    {
        return $this->qualifyColumn($this->getSiteIdColumn());
    }
}
