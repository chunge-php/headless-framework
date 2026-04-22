<?php

use function app\core\Foundation\manifest_group;

return [
    'name' => 'Uploader',
    'version' => '1.0.0',
    'display' => '文件上传管理',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => [
            'files'=> '^1.0.0',
        ],
        'features' => [],
    ],
    'provides' => [
        'features' => [],
    ],
];
