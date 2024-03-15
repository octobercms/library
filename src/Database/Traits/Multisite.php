<?php namespace October\Rain\Database\Traits;

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
            $otherSites = $otherModels->pluck('site_id')->all();

            // Propagate attributes to known records
            if ($this->propagatable) {
                foreach ($otherModels as $model) {
                    $this->propagateToSite($model->site_id, $model);
                }
            }

            // Sync non-existent records
            if ($this->isMultisiteSyncEnabled()) {
                $missingSites = array_diff($this->getMultisiteSyncSites(), $otherSites);
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
        if (!$this->isAttributePropagatable($name)) {
            return false;
        }

        if ($type === null) {
            $type = $this->getRelationType($name);
        }

        if (!in_array($type, ['belongsToMany', 'morphedByMany', 'belongsTo', 'hasOne', 'hasMany', 'attachOne', 'attachMany'])) {
            return false;
        }

        // The current record counts for one so halt if we find more
        return !($this->newOtherSiteQuery()->count() > 1);
    }

    /**
     * defineMultisiteRelation will modify defined relations on this model so they share
     * their association using the shared identifier (`site_root_id`). Only these relation
     * types support relation sharing: `belongsToMany`, `morphedByMany`,
     * `belongsTo`, `hasOne`, `hasMany`, `attachOne`, `attachMany`.
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
            if (in_array($type, ['belongsToMany', 'morphedByMany'])) {
                $this->$type[$name]['parentKey'] = 'site_root_id';
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

        return $otherModel;
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
     * isMultisiteSyncEnabled
     */
    public function isMultisiteSyncEnabled()
    {
        if (!property_exists($this, 'propagatableSync')) {
            return false;
        }

        if (!is_array($this->propagatableSync)) {
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

        return array_get($this->propagatableSync, $key, $default);
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
            $otherModel->save();
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
            $otherModel = $this->replicateWithRelations();
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
