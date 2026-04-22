<?php

use app\modules\myclass\CardPointeGateway;
use app\modules\myclass\CsvTemplate;
use app\modules\myclass\FileAliyun;
use app\modules\myclass\GoogleAuthService;
use app\modules\myclass\MailgunService;
use app\modules\myclass\SensitiveFilter;
use app\modules\myclass\TemplateImport;

return [
    'myclass.GoogleAuthService.getAuthUrl' => [GoogleAuthService::class, 'getAuthUrl'],
    'myclass.GoogleAuthService.getUserInfo' => [GoogleAuthService::class, 'getUserInfo'],
    'myclass.TemplateImport.import' => [TemplateImport::class, 'import'],
    'myclass.FileAliyun.getSignature' => [FileAliyun::class, 'getSignature'],
    'myclass.CsvTemplate.call' => [CsvTemplate::class, 'call'],
    'myclass.CsvTemplate.custom' => [CsvTemplate::class, 'custom'],
    'myclass.CardPointeGateway.createProfile' => [CardPointeGateway::class, 'createProfile'],
    'myclass.CardPointeGateway.updateProfile' => [CardPointeGateway::class, 'updateProfile'],
    'myclass.CardPointeGateway.authPayment' => [CardPointeGateway::class, 'authPayment'],
    'myclass.MailgunService.signUpHtml' => [MailgunService::class, 'signUpHtml'],
    'myclass.MailgunService.bindHtml'=>[MailgunService::class, 'bindHtml'],
    'myclass.MailgunService.sendEmail'=> [MailgunService::class, 'sendEmail'],
    'myclass.SensitiveFilter.detect'=> [SensitiveFilter::class, 'detect'],
    'myclass.SensitiveFilter.cleanAll'=> [SensitiveFilter::class, 'cleanAll'],
    'myclass.MailgunService.retrievePasswordHtml'=> [MailgunService::class, 'retrievePasswordHtml'],
];
