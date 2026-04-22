<?php
return [
    'name' => 'wallet',
    'version' => '1.0.0',
    'display' => '用户钱包',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => [
            'user'=> '^1.0.0',
        ],
        'features' => [
        ],
    ],
    'provides' => [
        'features' => [
            'user.wallet.create'=>'创建用户钱包',
            'user.wallet.getBalance'=>'获取用户钱包余额',
            'user.wallet.nextBillNo'=>'生成并发安全的账单号',
            'user.wallet.autoTopupStatus'=>'设置自动充值状态'
        ],
    ],
];
