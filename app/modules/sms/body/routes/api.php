<?php

use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\sms\body\controllers\BodyController;

Route::group('/sms/body', function () {
    Route::get('/index',   [BodyController::class, 'index'])->name('sms.body.index');   //列表
    Route::get('/monthIndex',   [BodyController::class, 'monthIndex'])->name('sms.body.monthIndex');   //月账单消费记录
    
    Route::post('/create',   [BodyController::class, 'create'])->name('sms.body.create');   //创建/修改
    Route::post('/anewSend',   [BodyController::class, 'anewSend'])->name('sms.body.anewSend');   //重新发送
    Route::get('/showIndex',   [BodyController::class, 'showIndex'])->name('sms.body.showIndex');   //详情
    Route::post('/delete',   [BodyController::class, 'delete'])->name('sms.body.delete');   //删除
})->middleware([JwtMiddleware::class]);
