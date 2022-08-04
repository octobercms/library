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
            $this->site_id = Site::getSiteIdFromContext();
        });
    }

    /**
     * newSiteQuery
     */
    public function newSiteQuery($siteId)
    {
        return $this->newQuery()->withSites()
            ->where(function($q) {
                $q->where('id', $this->site_root_id ?: $this->id);
                $q->orWhere('site_root_id', $this->site_root_id ?: $this->id);
            })
            ->where('site_id', $siteId)
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
            $otherModel->site_id = $siteId;
            $otherModel->site_root_id = $this->site_root_id ?: $this->id;
            $otherModel->save();
        }

        return $otherModel;
    }
}
