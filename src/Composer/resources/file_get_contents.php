<?php

/**
 * This prevents file_get_contents from throwing an exception.
 */
namespace Composer\Repository
{
    /**
     * file_get_contents is not suppressed in composer
     */
    function file_get_contents(...$args)
    {
        return @\file_get_contents(...$args);
    }
}
