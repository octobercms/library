<?php

return [

    'default' => 'sqlite',

    'connections' => [
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => env('DB_DATABASE', 'storage/database.sqlite'),
            'prefix'   => '',
        ],
    ],

];
