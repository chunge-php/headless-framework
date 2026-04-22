<?php

use function app\core\Foundation\manifest_group;

$provides =  manifest_group('auth.google.', [
    'registerFun' => '注册/登录',
]);
$features =  manifest_group('myclass.GoogleAuthService.', [
    'getAuthUrl' => '获取谷歌登录链接',
]);
$userInfo =  manifest_group('user.userInfo.', [
    'getUserInfo' => '用户基本信息',
    'create' => '创建用户基本信息',
    'getUserInfoToken' => '获取用户信息Token',
    'credentialCreate' => '创建用户凭证'
]);
return [
    'name' => 'Google',
    'version' => '1.0.0',
    'display' => '谷歌登录',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => [
            'myclass' => '>=1.0.0'
        ],
        'features' => array_merge($features,$userInfo,[
            'myclass.GoogleAuthService.getUserInfo'=>'获取谷歌用户信息',
            'user.wallet.create'=>'创建用户钱包'
        ]),
    ],
    'provides' => [
        'features' => $provides,
    ],
];
