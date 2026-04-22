<?php

use function app\core\Foundation\manifest_group;

return [
    'name' => 'Legal',
    'version' => '1.0.0',
    'display' => '用户协议等大文本内容',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => [
            'clutter'=>'^1.0.0'
        ],
        'features' => [],
    ],
    'provides' => [
        'features' => [],
    ],
];
