<?php

use October\Rain\Composer\ClassLoader;

ClassLoader::configure(dirname(__DIR__, 4))
    ->withNamespace('App\\', 'app')
    ->withDirectories([
        'modules',
        'plugins'
    ])
    ->register();
