<?php

use think\facade\Cache;

include '../vendor/autoload.php';

Cache::config([
    'default' => 'redis',
    'stores'  => [
        'redis' => [
            'type'     => 'redis',
            'host'     => '192.168.1.222',
            'port'     => 6379,
            'password' => 'foobared',
        ]
    ]
]);