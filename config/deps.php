<?php

return [
    "php" => ">=8.1",
    "workerman/webman-framework" => "^2.1",
    "monolog/monolog" => "^2.0", //日志
    "doctrine/inflector" => "^2.1", //Inflector
    "robmorgan/phinx" => "^0.16.10", //数据库迁移
    "webman/console" => "^2.1", //控制台
    "topthink/think-validate" => "^3.0", //验证
    "composer/semver" => "^3.4", //版本控制
    "vlucas/phpdotenv" => "^5.6", //环境变量
    "illuminate/database" => "^12.21", //数据库
    "illuminate/events" => "^12.24", //事件
    "webman/database" => "^2.1", //模型
    "symfony/translation" => "^7.3", //多语言
    "firebase/php-jwt" => "^6.11", //jwt令牌库
    "twilio/sdk" => "^8.8", //第三方短信SDK
    "webman/redis" => "^2.1", //redis
    "webman/redis-queue" => "^2.1", //redis队列 
    "google/apiclient" => "^2.18", //谷歌登录sdk
    "mailgun/mailgun-php"=>"^4.3",//邮件SDK
    "tinywan/exception-handler"=>"^1.6",//全局报错处理
    "qcloud/cos-sdk-v5"=>"^2.6",//腾讯cos
    "qiniu/php-sdk"=> "^7.14",//七牛对象存储
    "webman/cache"=> "^2.1",//缓存
    "alibabacloud/sdk"=> "^1.8",//阿里云sdk
    "alibabacloud/sts"=> "*",//阿里云sts
    "workerman/crontab"=> "^1.0"//定时任务


];