<?php

use function app\core\Foundation\manifest_group;

return [
    'name' => 'Templat',
    'version' => '1.0.0',
    'display' => '模板管理',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => [
            'templat'=>'^1.0.0',
        ],
        'features' => [],
    ],
    'provides' => [
        'features' => [],
    ],
];
