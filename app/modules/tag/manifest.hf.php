<?php

use function app\core\Foundation\manifest_group;

return [
    'name' => 'Tag',
    'version' => '1.0.0',
    'display' => '标签管理',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => [
            'tag'=>'^1.0.0'
        ],
        'features' => [],
    ],
    'provides' => [
        'features' => [
            'tag.mapByTargets'=>'标签绑定列表',
            'tag.createTagBook'=>'创建标签绑定',
            'tag.tagMerge'=>'标签合并',
            'tag.createTagBookBatch'=>'批量创建标签绑定',
            'tag.getIdArr'=>'标签ID列表',
            'tag.deleteTagBook'=>'删除标签绑定'
        ],
    ],
];
