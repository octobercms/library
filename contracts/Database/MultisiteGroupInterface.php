<?php namespace October\Contracts\Database;

/**
 * MultisiteGroupInterface
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface MultisiteGroupInterface
{
    /**
     * isMultisiteGroupEnabled
     * @return bool
     */
    public function isMultisiteGroupEnabled(): bool;

    /**
     * getSiteGroupIdColumn
     * @return string
     */
    public function getSiteGroupIdColumn(): string;

    /**
     * getQualifiedSiteGroupIdColumn
     * @return string
     */
    public function getQualifiedSiteGroupIdColumn(): string;
}
