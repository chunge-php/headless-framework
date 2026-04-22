<?php
// app/modules/auth-email/manifest.hf.php
use function app\core\Foundation\manifest_group;

$features =  manifest_group('sms.register.', [
    'send' => '发送验证码',
    'verifyCode' => '校验验证码',
]);
return [
    'name'    => 'sms-register',
    'version' => '1.0.0',
    'display' => '发送验证码',
    'requires' => [
        'php'      => '>=8.1',
        'webman'   => '>=1.5',
        'modules'  => [
            'sms'=> '^1.0.0',
        ],
        'features' => [],
    ],
    'provides' => [
        'features' => $features,
    ],
];
