<?php

use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\tag\controllers\TagController;

Route::group('/tag', function () {
    Route::get('/index',   [TagController::class, 'index'])->name('tag.index');   //列表
    Route::post('/create',   [TagController::class, 'create'])->name('tag.create');   //创建/修改
    Route::get('/show',   [TagController::class, 'show'])->name('tag.show');   //详情
    Route::post('/delete',   [TagController::class, 'delete'])->name('tag.delete');   //删除
    Route::get('/getNameIndex',   [TagController::class, 'getNameIndex'])->name('tag.getNameIndex');   //获取标签下拉
})->middleware([JwtMiddleware::class]);
