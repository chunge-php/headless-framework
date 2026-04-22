<?php

use function app\core\Foundation\manifest_group;

$features =  manifest_group('user.userInfo.', [
    'getUserInfo' => '用户基本信息',
    'create' => '创建用户基本信息',
    'identitiesCreate' => '创建用户身份信息',
    'identitiesUpdate' => '更新用户身份信息',
    'credentialCreate' => '创建用户凭证信息',
    'getUserInfoToken' => '获取用户信息并生成token',
    'getSecretHash' => '验证登录密码是否正确'
]);

return [
    'name' => 'userInfo',
    'version' => '1.0.0',
    'display' => '用户基本信息',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => [
            'user'=> '^1.0.0',
        ],
        'features' => [
            'clients.show'=>'获取密钥信息'
        ],
    ],
    'provides' => [
        'features' =>  $features,
    ],
];
