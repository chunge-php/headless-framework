<?php

use function app\core\Foundation\manifest_group;

return [
    'name' => 'Body',
    'version' => '1.0.0',
    'display' => '短信发送',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => [
            'sms'=> '^1.0.0',
            'batchLog'=> '^1.0.0',
        ],
        'features' => [
            'user.wallet.getBalance'=>'获取用户余额',
            'batchLog.create'=>'创建批次日志'
        ],
    ],
    'provides' => [
        'features' => [
            'sms.body.create' => '创建短信发送任务',
            'sms.body.delete'=>'删除短信发送任务'
        ],
    ],
];
