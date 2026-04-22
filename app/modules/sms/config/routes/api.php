<?php
use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\sms\config\controllers\ConfigController;
Route::group('/sms/config', function () {
    Route::get('/priceName',   [ConfigController::class, 'priceName'])->name('sms.config.priceName');   //列表
    Route::get('/index',   [ConfigController::class, 'index'])->name('sms.config.index');   //列表
    Route::post('/create',   [ConfigController::class, 'create'])->name('sms.config.create');   //创建/修改
    Route::get('/show',   [ConfigController::class, 'show'])->name('sms.config.show');   //详情
    Route::post('/delete',   [ConfigController::class, 'delete'])->name('sms.config.delete');   //删除
})->middleware([JwtMiddleware::class]);
