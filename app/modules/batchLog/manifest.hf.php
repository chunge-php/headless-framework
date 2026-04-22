<?php

use function app\core\Foundation\manifest_group;

return [
    'name' => 'Batchlog',
    'version' => '1.0.0',
    'display' => '批次任务记录',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => [],
        'features' => [],
    ],
    'provides' => [
        'features' => [
            'batchLog.create'=>'创建',
            'batchLog.update'=>'更新',
            'batchLog.delete'=>'更新',
        ],
    ],
];
