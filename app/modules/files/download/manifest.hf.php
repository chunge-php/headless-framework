<?php

use function app\core\Foundation\manifest_group;

return [
    'name' => 'Download',
    'version' => '1.0.0',
    'display' => '下载管理',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => [
            'files' => '^1.0.0',
        ],
        'features' => [
            'myclass.CsvTemplate.call' => '通过预设模板生成 CSV',
            'myclass.CsvTemplate.custom' => '生成自定义模板',
        ],
    ],
    'provides' => [
        'features' => [],
    ],
];
