<?php

use function app\core\Foundation\manifest_group;

return [
    'name' => 'Groupuser',
    'version' => '1.0.0',
    'display' => '分组用户管理',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => [
            'groupUser'=>'^1.0.0'
        ],
        'features' => [
            'tag.mapByTargets'=>'标签绑定列表',
            'tag.createTagBook'=>'创建标签绑定',
            'tag.tagMerge'=>'标签合并',
        ],
    ],
    'provides' => [
        'features' => [
            'groupUser.getIdArr'=>'获取分组用户id列表',
        ],
    ],
];
