<?php

return [
    'enabled' => env('BOOST_ENABLED', true),

    'log_path' => env('BOOST_LOG_PATH', storage_path('logs/boost.log')),

    'database' => [
        'default' => env('DB_CONNECTION', 'mysql'),
        'connections' => [],
    ],

    'tools' => [
        'enabled' => [
            'GetConfig',
            'DatabaseSchema',
            'DatabaseQuery',
            'ReadLogEntries',
            'ListRoutes',
        ],
    ],
];
