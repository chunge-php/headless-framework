<?php

use app\middleware\ApiAuthMiddleware;
use app\middleware\ApiParamValidate;
use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\api\clients\controllers\ClientsController;

Route::get('/api/clients/show',   [ClientsController::class, 'show'])->name('api.clients.show')->middleware([JwtMiddleware::class]);   //详情
Route::post('/api/clients/status',   [ClientsController::class, 'status'])->name('api.clients.status')->middleware([JwtMiddleware::class]);   //详情

Route::group('/api/clients', function () {
    Route::post('/sendSms',   [ClientsController::class, 'sendSms'])->name('api.clients.sendSms');   //发送短信
    Route::post('/createSmsAccount',   [ClientsController::class, 'createSmsAccount'])->name('api.clients.createSmsAccount');   //创建短信平台账号
    Route::post('/getBalance',   [ClientsController::class, 'getBalance'])->name('api.clients.getBalance');   //获取短信平台余额
    Route::post('/getSmsToken',   [ClientsController::class, 'getSmsToken'])->name('api.clients.getSmsToken');   //获取Token
})->middleware([ApiAuthMiddleware::class, ApiParamValidate::class]);

