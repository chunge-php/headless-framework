<?php

use function app\core\Foundation\manifest_group;

return [
    'name' => 'Import',
    'version' => '1.0.0',
    'display' => '导入',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => [
            'files' => '^1.0.0',
            'batchLog' => '^1.0.0'
        ],
        'features' => [
            'batchLog.create' => '创建'
        ],
    ],
    'provides' => [
        'features' => [],
    ],
];
