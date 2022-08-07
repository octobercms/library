<?php namespace October\Rain\Database\Traits;

use Site;
use October\Rain\Database\Scopes\MultisiteScope;

/**
 * Multisite trait allows for site-based of models, the database
 * table should contain site_id and site_root_id keys
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait Multisite
{
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
     * findOrCreateForSite
     */
    public function findOrCreateForSite($siteId)
    {
        $otherModel = $this->newSiteQuery($siteId)->first();

        // Replicate
        if (!$otherModel) {
            $otherModel = $this->replicate();
            $otherModel->{$this->getSiteIdColumn()} = $siteId;
            $otherModel->site_root_id = $this->site_root_id ?: $this->id;
            $otherModel->save();
        }

        return $otherModel;
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
