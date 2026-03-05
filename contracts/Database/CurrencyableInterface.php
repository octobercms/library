<?php namespace October\Contracts\Database;

/**
 * CurrencyableInterface
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface CurrencyableInterface
{
    /**
     * getCurrencyableAttributes returns the list of currencyable attribute names
     * @return array
     */
    public function getCurrencyableAttributes();

    /**
     * isCurrencyableAttribute checks if a given attribute is currencyable
     * @return bool
     */
    public function isCurrencyableAttribute($key);

    /**
     * isCurrencyableEnabled
     * @return bool
     */
    public function isCurrencyableEnabled();
}
