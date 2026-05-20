<?php

return [

    'default' => env('REVERB_SERVER', 'reverb'),

    'servers' => [

        'reverb' => [

            'host' => env('REVERB_HOST', '0.0.0.0'),

            'port' => env('REVERB_PORT', 8080),

            'hostname' => env('REVERB_HOSTNAME', 'localhost'),

            'options' => [],

            'max_request_size' => 10_000,

            'scaling' => [

                'enabled' => false,

                'channel' => env('REVERB_SCALING_CHANNEL', 'reverb'),

                'server' => [

                    'url' => env('REDIS_URL'),
                ],
            ],

            'pulse_ingest_interval' => 15,

            'telescope_ingest_interval' => 15,
        ],
    ],

    'apps' => [

        'provider' => 'config',

        'apps' => [

            [

                'app_id' => env('REVERB_APP_ID'),

                'key' => env('REVERB_APP_KEY'),

                'secret' => env('REVERB_APP_SECRET'),

                'ping_interval' => 60,

                'activity_timeout' => 30,

                'allowed_origins' => ['*'],

                'max_message_size' => 10_000,

                'options' => [

                    'host' => env('REVERB_HOST'),

                    'port' => env('REVERB_PORT'),

                    'scheme' => env('REVERB_SCHEME', 'http'),
                ],
            ],
        ],
    ],
];
