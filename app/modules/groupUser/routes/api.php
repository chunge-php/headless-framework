<?php
use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\groupUser\controllers\GroupUserController;
Route::group('/groupUser', function () {
    Route::get('/index',   [GroupUserController::class, 'index'])->name('groupUser.index');   //列表
    Route::post('/create',   [GroupUserController::class, 'create'])->name('groupUser.create');   //创建/修改
    Route::get('/show',   [GroupUserController::class, 'show'])->name('groupUser.show');   //详情
    Route::post('/delete',   [GroupUserController::class, 'delete'])->name('groupUser.delete');   //删除
    Route::post('/tagMerge',   [GroupUserController::class, 'tagMerge'])->name('groupUser.tagMerge');   //合并标签
})->middleware([JwtMiddleware::class]);
