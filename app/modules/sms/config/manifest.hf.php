<?php

use function app\core\Foundation\manifest_group;

return [
    'name' => 'Config',
    'version' => '1.0.0',
    'display' => '短信配置',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => [
            'sms'=> '^1.0.0',

        ],
        'features' => [],
    ],
    'provides' => [
        'features' => [
            'sms.config.getPriceId'=>'根据短信类型获取价格id'
        ],
    ],
];
