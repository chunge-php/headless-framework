<?php

use function app\core\Foundation\manifest_group;

return [
    'name' => 'Card',
    'version' => '1.0.0',
    'display' => '信用卡管理',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => [
            'user' => '^1.0.0'
        ],
        'features' => [
            'myclass.CardPointeGateway.createProfile' => '用户绑定信用卡',
            'myclass.CardPointeGateway.updateProfile' => '用户更新信用卡信息',
            'user.wallet.autoTopupStatus'=>'自动扣款开关'
        ],
    ],
    'provides' => [
        'features' => [],
    ],
];
