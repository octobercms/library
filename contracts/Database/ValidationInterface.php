<?php namespace October\Contracts\Database;

/**
 * ValidationInterface
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface ValidationInterface
{
    /**
     * setValidationAttributeNames
     */
    public function setValidationAttributeNames($attributeNames);

    /**
     * isAttributeRequired
     * @return bool
     */
    public function isAttributeRequired($attribute, $checkDependencies = true);
}
