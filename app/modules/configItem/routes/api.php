<?php

use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\configItem\controllers\ConfigItemController;

Route::group('/configItems', function () {
    Route::get('/index',   [ConfigItemController::class, 'index'])->name('configItem.index');   //列表
    Route::get('/getName',   [ConfigItemController::class, 'getName'])->name('configItem.getName');   //根据signs获取列表
    Route::post('/create',   [ConfigItemController::class, 'create'])->name('configItem.create');   //创建/修改
    Route::get('/show',   [ConfigItemController::class, 'show'])->name('configItem.show');   //详情
    Route::post('/delete',   [ConfigItemController::class, 'delete'])->name('configItem.delete');   //删除
})->middleware([JwtMiddleware::class]);
