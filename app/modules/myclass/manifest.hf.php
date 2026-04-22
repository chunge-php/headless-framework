<?php
return [
    'name' => 'myclass',
    'version' => '1.0.0',
    'display' => '自定义工具类',
    'requires' => [
        'php' => '>=8.0',
        'webman' => '>=1.5',
        'modules' => [
            'myclass' => '^1.0.0',
        ],
        'features' => [],
    ],
    'provides' => [
        'features' => [
            'myclass.GoogleAuthService.getAuthUrl' => '获取谷歌登录链接',
            'myclass.GoogleAuthService.getUserInfo' => '获取谷歌用户信息',
            'myclass.TemplateImport.import' => '导入模板',
            'myclass.FileAliyun.getSignature' => '获取阿里云文件上传签名',
            'myclass.CsvTemplate.call' => '通过预设模板生成 CSV',
            'myclass.CsvTemplate.custom' => '生成自定义模板',
            'myclass.CardPointeGateway.createProfile' => '用户绑定信用卡',
            'myclass.CardPointeGateway.updateProfile' => '用户更新信用卡信息',
            'myclass.CardPointeGateway.authPayment'=>'发起支付',
            'myclass.MailgunService.signUpHtml'=>'邮件模板生成',
            'myclass.MailgunService.bindHtml'=>'绑定验证码邮件模板',
            'myclass.MailgunService.sendEmail'=>'发送邮件',
            'myclass.SensitiveFilter.detect'=>'敏感词过滤',
            'myclass.SensitiveFilter.cleanAll'=>'清洗敏感词',
            'myclass.MailgunService.retrievePasswordHtml'=>'重置密码邮件模板'
            
        ],
    ],
];
