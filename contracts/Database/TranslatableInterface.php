<?php namespace October\Contracts\Database;

/**
 * TranslatableInterface
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface TranslatableInterface
{
    /**
     * getTranslatableAttributes returns the list of translatable attribute names
     * @return array
     */
    public function getTranslatableAttributes();

    /**
     * isTranslatableAttribute checks if a given attribute is translatable
     * @return bool
     */
    public function isTranslatableAttribute($key);

    /**
     * isTranslatableEnabled
     * @return bool
     */
    public function isTranslatableEnabled();
}
