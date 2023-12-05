<?php namespace October\Contracts\Twig;

/**
 * ForwardsAttributes implements custom accessor logic for attributes used exclusively in Twig
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface ForwardsAttributes
{
    /**
     * getTwigAttribute returns a list of method names that can be called from Twig.
     * This method should return null/void to fallback to the default access logic.
     */
    public function getTwigAttribute($attribute);
}
