<?php namespace October\Rain\Assetic\Factory\Resource;

/**
 * DirectoryResourceIterator is an iterator that converts file objects into file resources.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 * @access private
 */
class DirectoryResourceIterator extends \RecursiveIteratorIterator
{
    /**
     * current
     */
    public function current()
    {
        return new FileResource(parent::current()->getPathname());
    }
}
