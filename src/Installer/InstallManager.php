<?php

namespace October\Rain\Install;

use App;

/**
 * InstallManager
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class InstallManager
{
    /**
     * instance creates a new instance of this singleton
     */
    public static function instance(): static
    {
        return App::make('system.installer');
    }
}
