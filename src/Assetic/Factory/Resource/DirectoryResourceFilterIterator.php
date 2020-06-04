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
 * Filters files by a basename pattern.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 * @access private
 */
class DirectoryResourceFilterIterator extends \RecursiveFilterIterator
{
    protected $pattern;

    public function __construct(\RecursiveDirectoryIterator $iterator, $pattern = null)
    {
        parent::__construct($iterator);

        $this->pattern = $pattern;
    }

    public function accept()
    {
        $file = $this->current();
        $name = $file->getBasename();

        if ($file->isDir()) {
            return '.' != $name[0];
        }

        return null === $this->pattern || 0 < preg_match($this->pattern, $name);
    }

    public function getChildren()
    {
        return new self(new \RecursiveDirectoryIterator($this->current()->getPathname(), \RecursiveDirectoryIterator::FOLLOW_SYMLINKS), $this->pattern);
    }
}
