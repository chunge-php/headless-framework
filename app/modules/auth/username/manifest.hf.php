<?php

use function app\core\Foundation\manifest_group;

$features =  manifest_group('auth.username.', [
    'unifyLogin' => '统一登录',
]);
$userInfo =  manifest_group('user.userInfo.', [
    'getUserInfo' => '用户基本信息',
    'getUserInfoToken' => '获取用户信息Token',
    'getSecretHash' => '验证登录密码是否正确'
]);
return [
    'name' => 'Username',
    'version' => '1.0.0',
    'display' => '用户名登录',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => [],
        'features' => [],
    ],
    'provides' => [
        'features' => $features,
    ],
];
