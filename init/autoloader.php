<?php

use October\Rain\Composer\ClassLoader;

ClassLoader::configure(dirname(__DIR__))
    ->withNamespace('App\\', '')
    ->withDirectories([
        'modules',
        'plugins'
    ])
    ->register();
