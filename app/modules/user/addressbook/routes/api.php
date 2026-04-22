<?php
use app\middleware\JwtMiddleware;
use Webman\Route;
use app\modules\user\addressbook\controllers\AddressbookController;

Route::group('/user/addressbook', function () {
    Route::get('/index',   [AddressbookController::class, 'index'])->name('user.addressbook.index');   //列表
    Route::post('/create',   [AddressbookController::class, 'create'])->name('user.addressbook.create');   //创建/修改
    Route::get('/show',   [AddressbookController::class, 'show'])->name('user.addressbook.show');   //详情
    Route::post('/delete',   [AddressbookController::class, 'delete'])->name('user.addressbook.delete');   //删除
    Route::post('/tagMerge',   [AddressbookController::class, 'tagMerge'])->name('user.addressbook.tagMerge');   //合并标签
    
})->middleware([JwtMiddleware::class]);
