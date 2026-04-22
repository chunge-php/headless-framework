<?php

use function app\core\Foundation\manifest_group;

return [
    'name' => 'addressbook',
    'version' => '1.0.0',
    'display' => '通讯录',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => [
            'user' => '^1.0.0'
        ],
        'features' => [
            'tag.createTagBook'=>'创建标签绑定',
            'tag.tagMerge'=>'标签合并',
            'tag.mapByTargets'=>'标签绑定列表'
        ],
    ],
    'provides' => [
        'features' => [
            'user.addressbook.getDateUser'=>'根据生日获取用户'
        ],
    ],
];
