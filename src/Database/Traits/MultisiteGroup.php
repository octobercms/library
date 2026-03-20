<?php namespace October\Rain\Database\Traits;

use Site;
use October\Rain\Database\Scopes\MultisiteGroupScope;

/**
 * MultisiteGroup trait scopes records by site_group_id (tenant).
 *
 * Unlike the Multisite trait which creates duplicate rows per locale (site_id),
 * this trait scopes a single record to a site group. Language/translation is
 * handled separately by the Translate plugin via $translatable.
 *
 * The database table should contain a site_group_id column.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
trait MultisiteGroup
{
    /**
     * bootMultisiteGroup trait for a model.
     */
    public static function bootMultisiteGroup()
    {
        static::addGlobalScope(new MultisiteGroupScope);
    }

    /**
     * initializeMultisiteGroup
     */
    public function initializeMultisiteGroup()
    {
        $this->bindEvent('model.beforeSave', [$this, 'multisiteGroupBeforeSave']);
    }

    /**
     * multisiteGroupBeforeSave sets the site_group_id from context
     */
    public function multisiteGroupBeforeSave()
    {
        if (Site::hasGlobalContext()) {
            return;
        }

        $this->{$this->getSiteGroupIdColumn()} = Site::getSiteGroupIdFromContext();
    }

    /**
     * isMultisiteGroupEnabled allows for programmatic toggling
     */
    public function isMultisiteGroupEnabled(): bool
    {
        return true;
    }

    /**
     * getSiteGroupIdColumn gets the name of the "site group id" column.
     */
    public function getSiteGroupIdColumn(): string
    {
        return defined('static::SITE_GROUP_ID') ? static::SITE_GROUP_ID : 'site_group_id';
    }

    /**
     * getQualifiedSiteGroupIdColumn gets the fully qualified "site group id" column.
     */
    public function getQualifiedSiteGroupIdColumn(): string
    {
        return $this->qualifyColumn($this->getSiteGroupIdColumn());
    }
}
