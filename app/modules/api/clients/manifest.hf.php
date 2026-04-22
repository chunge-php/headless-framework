<?php

use function app\core\Foundation\manifest_group;

return [
    'name' => 'Clients',
    'version' => '1.0.0',
    'display' => '客户端key值管理',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => [],
        'features' => [
            'sms.config.getPriceId'=>'根据类型获取短信价格id',
            'user.wallet.getBalance'=>'获取用户余额'
        ],
    ],
    'provides' => [
        'features' => [
            'clients.show'=>'获取密钥信息'
        ],
    ],
];
