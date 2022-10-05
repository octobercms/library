<?php namespace October\Contracts\Database;

/**
 * MultisiteInterface
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface MultisiteInterface
{
    /**
     * findOrCreateForSite
     */
    public function findOrCreateForSite(string $siteId = null);

    /**
     * isMultisiteEnabled
     * @return bool
     */
    public function isMultisiteEnabled();
}
