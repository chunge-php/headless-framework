<?php

use function app\core\Foundation\manifest_group;

return [
    'name' => 'Jobtask',
    'version' => '1.0.0',
    'display' => '任务管理',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => ['user.addressbook' => '^1.0.0', 'tag' => '^1.0.0','groupUser'=>'^1.0.0'],
        'features' => [
            'user.addressbook.getDateUser' => '根据日期获取用户列表',
            'tag.getIdArr' => '获取标签id数组',
            'groupUser.getIdArr'=>'获取用户组id数组',
        ],
    ],
    'provides' => [
        'features' => [],
    ],
];
