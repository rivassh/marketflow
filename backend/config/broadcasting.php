<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    |
    | This option controls the default broadcaster used by the framework for
    | broadcasting. You may set this to "pusher" or "redis" or "log" or
    | "array" or "null" for the default. If you choose "null", all events
    | will be broadcast through the log, but not actually delivered.
    */

    'default' => env('BROADCAST_DRIVER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    |
    | This is where you may define all of the broadcast connections supported
    | by your application. A default connection of "null" is provided for
    | your convenience. You may add more connections as needed.
    */

    'connections' => [

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
                'useTLS' => false,
                'host' => '127.0.0.1',
                'port' => 6001,
                'scheme' => 'http',
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
            'channel' => env('LOG_CHANNEL'),
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
