<?php
return [
    'import-address-book'  => [
        'handler'     => Webman\RedisQueue\Process\Consumer::class,
        'count'       => 1, // 可以设置多进程同时消费
        'constructor' => [
            // 消费者类目录
            'consumer_dir' => app_path() . '/queue/redis/import'
        ]
    ],
    'sms'  => [
        'handler'     => Webman\RedisQueue\Process\Consumer::class,
        'count'       => 4, // 可以设置多进程同时消费
        'constructor' => [
            // 消费者类目录
            'consumer_dir' => app_path() . '/queue/redis/sms'
        ]
    ],
    'email'  => [
        'handler'     => Webman\RedisQueue\Process\Consumer::class,
        'count'       => 1, // 可以设置多进程同时消费
        'constructor' => [
            // 消费者类目录
            'consumer_dir' => app_path() . '/queue/redis/email'
        ]
    ],
    'wallet'  => [
        'handler'     => Webman\RedisQueue\Process\Consumer::class,
        'count'       => 1, // 可以设置多进程同时消费
        'constructor' => [
            // 消费者类目录
            'consumer_dir' => app_path() . '/queue/redis/wallet'
        ]
    ],
    'bill'  => [
        'handler'     => Webman\RedisQueue\Process\Consumer::class,
        'count'       => 1, // 可以设置多进程同时消费
        'constructor' => [
            // 消费者类目录
            'consumer_dir' => app_path() . '/queue/redis/bill'
        ]
    ],
    'game'  => [
        'handler'     => Webman\RedisQueue\Process\Consumer::class,
        'count'       => 1, // 可以设置多进程同时消费
        'constructor' => [
            // 消费者类目录
            'consumer_dir' => app_path() . '/queue/redis/game'
        ]
    ],
];
