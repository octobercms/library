<?php namespace October\Rain\Database\Traits;

use Site;
use October\Rain\Database\Scopes\MultisiteScope;

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
        $this->bindEvent('model.beforeSave', function() {
            $this->{$this->getSiteIdColumn()} = Site::getSiteIdFromContext();
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
     * newSiteQuery
     */
    public function newSiteQuery($siteId)
    {
        return $this->newQueryWithoutScopes()
            ->where(function($q) {
                $q->where('id', $this->site_root_id ?: $this->id);
                $q->orWhere('site_root_id', $this->site_root_id ?: $this->id);
            })
            ->where($this->getSiteIdColumn(), $siteId)
        ;
    }

    /**
     * propagateToSite will save propagated fields to other records
     */
    public function propagateToSite($siteId)
    {
        if ($this->isModelUsingSameSite($siteId)) {
            return;
        }

        $otherModel = $this->findOtherSiteModel($siteId);

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
     * findOtherModel
     */
    protected function findOtherSiteModel($siteId = null)
    {
        if ($siteId === null) {
            $siteId = Site::getSiteIdFromContext();
        }

        if ($this->isModelUsingSameSite($siteId)) {
            return $this;
        }

        $otherModel = $this->newSiteQuery($siteId)->first();

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
