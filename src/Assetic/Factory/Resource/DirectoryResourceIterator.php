<?php namespace October\Rain\Assetic\Factory\Resource;

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2014 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * An iterator that converts file objects into file resources.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 * @access private
 */
class DirectoryResourceIterator extends \RecursiveIteratorIterator
{
    public function current()
    {
        return new FileResource(parent::current()->getPathname());
    }
}
