<?php

/**
 * This file registers a null functions for specific packages where putenv()
 * is disabled since it is not necessary for web runtime execution.
 */
namespace Composer\Util
{
    /**
     * putenv cannot be removed so we suppress it
     */
    function putenv()
    {
        // Do nothing
    }
}
