<?php

return [
    'devices' => [
        'default' => [
            'ip' => env('ZKTECO_IP', '192.168.0.30'),
            'port' => env('ZKTECO_PORT', 4370),
            'comm_key' => env('ZKTECO_COMM_KEY', 2121),
        ]
    ]
];