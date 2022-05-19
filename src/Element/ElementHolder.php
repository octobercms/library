<?php namespace October\Rain\Element;

use October\Rain\Support\Collection;
use IteratorAggregate;
use Traversable;

/**
 * ElementHolder is a general collection used when passing a group of elements by reference,
 * allowing access via array and object notation.
 *
 * @package october\element
 * @author Alexey Bobkov, Samuel Georges
 */
class ElementHolder extends ElementBase implements IteratorAggregate
{
    /**
     * getIterator for the elements.
     */
    public function getIterator(): Traversable
    {
        return new Collection($this->config);
    }
}
