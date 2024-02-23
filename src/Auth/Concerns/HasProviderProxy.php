<?php namespace October\Rain\Auth\Concerns;

/**
 * HasProviderProxy provides proxy methods to emulate Laravel's auth provider
 *
 * @package october\auth
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasProviderProxy
{
    /**
     * getProvider just passes it back to the current class
     */
    public function getProvider()
    {
        return $this;
    }

    /**
     * getModel returns the class name for the user model
     */
    public function getModel()
    {
        return $this->userModel;
    }
}
