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
     * protected $propagatable = [];
     */

    /**
     * @var bool propagatableSync will enforce model structures between all sites
     *
     * protected $propagatableSync = true;
     */

    /**
     * bootMultisite trait for a model.
     */
    public static function bootMultisite()
    {
        static::addGlobalScope(new MultisiteScope);
    }

    /**
     * addPropagatable attributes for the model.
     * @param  array|string|null  $attributes
     */
    public function addPropagatable($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->propagatable = array_merge($this->propagatable, $attributes);
    }

    /**
     * initializeMultisite
     */
    public function initializeMultisite()
    {
        if (!is_array($this->propagatable)) {
            throw new Exception(sprintf(
                'The $propagatable property in %s must be an array to use the Multisite trait.',
                get_class($this)
            ));
        }

        $this->bindEvent('model.beforeSave', function() {
            if (MultisiteScope::hasConstraints()) {
                $this->{$this->getSiteIdColumn()} = Site::getSiteIdFromContext();
            }
        });

        $this->bindEvent('model.afterSave', function() {
            if ($this->getSaveOption('propagate') === true) {
                MultisiteScope::noConstraints(function() {
                    $this->afterSavePropagate();
                });
            }
        });
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
        return property_exists($this, 'propagatableSync')
            ? (bool) $this->propagatableSync
            : false;
    }

    /**
     * getMultisiteSyncSites
     * @return array
     */
    public function getMultisiteSyncSites()
    {
        return Site::listSiteIds();
    }

    /**
     * afterSavePropagate event
     */
    public function afterSavePropagate()
    {
        if (!$this->isMultisiteEnabled()) {
            return;
        }

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
    }

    /**
     * newOtherSiteQuery
     */
    public function newOtherSiteQuery()
    {
        return $this->newQueryWithoutScopes()
            ->where(function($q) {
                $q->where('id', $this->site_root_id ?: $this->id);
                $q->orWhere('site_root_id', $this->site_root_id ?: $this->id);
            });
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

        // Other model already exists, so propagate
        if ($otherModel->exists) {
            foreach ($this->propagatable as $field) {
                $otherModel->$field = $this->$field;
            }
        }

        $otherModel->save();

        return $otherModel;
    }

    /**
     * findOrCreateForSite
     */
    public function findOrCreateForSite($siteId = null)
    {
        $otherModel = $this->findOtherSiteModel($siteId);

        if (!$otherModel->exists) {
            $otherModel->save();
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

        $otherModel = $this
            ->newOtherSiteQuery()
            ->where($this->getSiteIdColumn(), $siteId)
            ->first();

        // Replicate without save
        if (!$otherModel) {
            $otherModel = $this->replicate();
            $otherModel->{$this->getSiteIdColumn()} = $siteId;
            $otherModel->site_root_id = $this->site_root_id ?: $this->id;
        }

        return $otherModel;
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
