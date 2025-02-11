<?php

return [
    'devices' => [
        'default' => [
            'ip' => env('ZKTECO_IP', '192.168.0.30'),
            'port' => (int) env('ZKTECO_PORT', 4370)
        ]
    ]
];