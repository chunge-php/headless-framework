<?php
// app/modules/auth-email/manifest.hf.php

use function app\core\Foundation\manifest_group;

$provides =  manifest_group('auth.email.', [
    'sendRegister' => '邮箱注册',
]);
$userInfo =  manifest_group('user.userInfo.', [
    'getUserInfo' => '用户基本信息',
    'create' => '创建用户基本信息',
    'getUserInfoToken' => '获取用户信息Token',
    'credentialCreate' => '创建用户凭证'
]);
$wallet =  manifest_group('user.wallet.', [
    'create' => '创建用户钱包'
]);

$requires  = array_merge($userInfo, $wallet, [
    'sms.register.verifyCode' => '验证码验证'
]);
return [
    'name'    => 'email',
    'version' => '1.0.0',
    'display' => '邮箱验证码登录/注册',
    'requires' => [
        'php'      => '>=8.1',
        'webman'   => '>=1.5',
        'modules'  => [
            'user' => '^1.0.0',
            'user.userInfo' => '^1.0.0',
            'user.wallet' => '^1.0.0',
        ],
        'features' => $requires,
    ],
    'provides' => [
        'features' => $provides,
    ],
    'migrations' => null
];
