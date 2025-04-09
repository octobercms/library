<?php

namespace October\Rain\Install;

use App;

/**
 * InstallerManager
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class InstallerManager
{
    /**
     * instance creates a new instance of this singleton
     */
    public static function instance(): static
    {
        return App::make('system.installer');
    }
}
