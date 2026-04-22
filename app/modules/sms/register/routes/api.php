<?php

use app\modules\sms\register\controllers\SmsController;
use Webman\Route;

Route::group('/sms', function () {
    Route::post('/send',   [SmsController::class, 'send']);   //发送验证码
    Route::post('/verifyCode',   [SmsController::class, 'verifyCode']);   //验证验证码
    
});
