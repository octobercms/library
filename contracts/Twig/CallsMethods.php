<?php namespace October\Contracts\Twig;

/**
 * CallsMethods from Twig engine
 *
 * @package october\contracts
 * @author Alexey Bobkov, Samuel Georges
 */
interface CallsMethods
{
    /**
     * getTwigMethodNames returns a list of method names that can be called from Twig.
     */
    public function getTwigMethodNames(): array;
}
